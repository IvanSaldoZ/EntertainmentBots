<?php namespace Cwtuning\Bots\Classes\Bots;

use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Classes\Google;

use Cwtuning\Bots\Models\YouTuberChannels as YouTuberChannelsModel;
use Cwtuning\Bots\Models\YouTuberVideos as YouTuberVideosModel;
use Cwtuning\Bots\Models\YouTuberComments as YouTuberCommentsModel;
use Cwtuning\Bots\Models\YouTuberVideosTitleFilter as YouTuberVideosTitleFilterModel;
use Cwtuning\Bots\Models\YouTuberPlaylists;
use Cwtuning\Bots\Models\YouTuberVideos;
use Cwtuning\Bots\Models\Bot as BotModel;

use Db;


/*
 *
 * Бот для добавления видео с плейлиста ютуба в базу данных
 *
 */

class BotYouTuber extends Bot
{

    const __BOT_NAME='YouTuberAddAllBot';
    const __SITE_NAME = 'YouTube'; //Отобажаемое название сайта


    protected $channel_last_id = '';



    /*
     *
     * Сканируем каналы из БД для добавления информации о видео в БД
     *
     */
    public function ScanChannels()
    {

        $res = 0;

        $YouTuberChannelName = YouTuberChannelsModel::where('is_on', 1)
            //->where('last_scanned', '>', $this->today)
            ->OrderBy('id', 'asc')
            ->where('id', '>', $this->channel_last_id)
            ->first();

        if ($YouTuberChannelName)
        {
            echo '<br><br><b>[' . $YouTuberChannelName->channel_caption . ']</b> - начало обработки канала<br>';

            $ChannelID = $YouTuberChannelName->channel_id;

            $pageToken = $YouTuberChannelName->last_page_token;

            $this->GetVideosFromChannel($ChannelID, $pageToken);

            $YouTuberChannelName->last_scanned = $this->today;

            sleep(1);//чтобы не создавать нагрузку на YouTube делаем проверку каналов раз в секунду

            //var_dump($VideoList);
            //echo $YouTuberChannelName->channel_id;
            //echo $YouTuberChannelName->last_scanned;
            $res = $YouTuberChannelName->id;
        }

        return $res;

    }


    /*
     *
     * Получаем массив видео с заданного канала
     *
     */
    public function GetVideosFromChannel($chname, $pageToken)
    {

        //$chname = 'UC0lT9K8Wfuc1KPqm6YjRf1A';

        $params = array(
            'part' => 'snippet,contentDetails,statistics',
            'id' => $chname,
        );

        $Google = new Google();

        //Получаем категории
        $obj = $Google->method('channels', $params);

        //var_dump($obj);

        $PlaylistsCount = YouTuberPlaylists::where('channel_id', $chname)
            ->where('is_on', 1)
            ->count();
        $PlaylistsArray = [];
        if ($PlaylistsCount > 0)
        {
            $PlaylistsArrayAll = YouTuberPlaylists::where('channel_id', $chname)
                ->where('is_on', 1)
                ->get();
            //var_dump($PlaylistsArray[0]->attributes['playlist_id']);
            foreach ($PlaylistsArrayAll as $PlaylistsArrayAllItem)
            {
                $PlaylistsArray[] = $PlaylistsArrayAllItem->playlist_id;
            }
        } else
        {
            //Получаем ID списка с загруженными видео
            $upload_playlist_name = $obj->items[0]->contentDetails->relatedPlaylists->uploads;
            $PlaylistsArray = [0 => $upload_playlist_name];

        }

        // var_dump($PlaylistsArray);

        //Для каждого плейлиста - добавляем из него видео
        foreach ($PlaylistsArray as $PlayListName)
        {

            //echo $PlayListName->playlist_id;
            //$PlayListArrayItem = 'PLQppxCvWoJk1bZs3Y9k6skAj0JWlvkRiH';
            //var_dump($PlayListName);
            //Получаем список видео, загруженных на канал
            $obj2 = $this->GetVideos($PlayListName, $pageToken);

            //Обновляем данные для следующего сканирования - сохраняем номер следующей страницы или отключаем бота, если все уже просканировано
            $YouTuberChannelsModel = YouTuberChannelsModel::where('channel_id', $chname)->first();
            if (isset($obj2->nextPageToken))
            {
                $nextPageToken = $obj2->nextPageToken;
                $YouTuberChannelsModel->last_page_token = $nextPageToken;
            } else
            {
                /////////////////// - для полного сканирования всех каналов - раскомментить $YouTuberChannelsModel->is_on = 0;
            }
            $YouTuberChannelsModel->Update();

            //Добавляем каждое видео канала в базу данных сайта
            $videoList = $obj2->items;

            //Сохраняем полученный объект в базу данных
            $this->SaveVideosAndCommentsToDatabase($videoList);
        }

    }


    /*
     *
     * Сохраняем объект массива видео и комментариев к ним в базу данных
     * https://developers.google.com/youtube/v3/docs/playlistItems/list#try-it
     *
     */
    public function SaveVideosAndCommentsToDatabase($VideosList)
    {

        foreach ($VideosList as $VideoItem)
        {
            $CurVideoID = $VideoItem->snippet->resourceId->videoId;

            $channel_id = $VideoItem->snippet->channelId;
            $title = $VideoItem->snippet->title;
            $is_filtered_ok = $this->Filter_IsAllowedToPublish($title, $channel_id);
            if ($title == 'Private video') $is_filtered_ok = 0; //Если видео удалено - то всё, не добавляем
            if ($title == 'Deleted video') $is_filtered_ok = 0; //Если видео удалено - то всё, не добавляем


            $VideoItemNewModelCount = YouTuberVideosModel::where('video_id', $CurVideoID)->count();

            //Добавляем видео в список только в том случае, если его ещё в этом списке нет
            if ($VideoItemNewModelCount == 0)
            {

                if ($is_filtered_ok == 1)
                {

                    //Получаем кол-во лайков и дизлайков для данного видео
                    $VideoStat = $this->GetVideoStat($CurVideoID);

                    $VideoItemNewModel = new YouTuberVideosModel();
                    $VideoItemNewModel->video_id = $CurVideoID;
                    $VideoItemNewModel->title = $VideoItem->snippet->title;
                    $VideoItemNewModel->published_at = $this->today;
                    $VideoItemNewModel->channel_id = $VideoItem->snippet->channelId;
                    $VideoItemNewModel->description = $VideoItem->snippet->description;
                    $VideoItemNewModel->pluses = $VideoStat->items[0]->statistics->likeCount;
                    $VideoItemNewModel->minuses = $VideoStat->items[0]->statistics->dislikeCount;

                    $VideoItemNewModel->Save();

                    echo 'Видео "' . $VideoItem->snippet->title . '" <b><font color="#006400">добавлено</font></b> в базу данных<br>';

                    //Добавляем в базу 100 комментариев к этому видео, чтобы потом их добавить в новость
                    $comments = $this->GetComments($CurVideoID);
                    $comments_array = $comments->items;
                    //Если есть комменты
                    if ($comments_array)
                    {

                        foreach ($comments_array as $comment)
                        {
                            $comment_text = $comment->snippet->topLevelComment->snippet;
                            $UniqueID = $comment->snippet->topLevelComment->id;
                            $is_comment = YouTuberCommentsModel::where('unique_id', $UniqueID)->count();

                            //Добавляем только тот коммент, которого ещё нет в базе
                            if ($is_comment == 0)
                            {
                                $newTime = str_replace("T", " ", $comment_text->publishedAt);
                                $newTime = str_replace(".000Z", "", $newTime);

                                $CommentNewModel = new YouTuberCommentsModel();
                                $CommentNewModel->unique_id = $UniqueID;
                                $CommentNewModel->video_id = $CurVideoID;
                                $CommentNewModel->comment = $comment_text->textDisplay;
                                $CommentNewModel->author_display_name = $comment_text->authorDisplayName;
                                $CommentNewModel->author_profile_image_url = $comment_text->authorProfileImageUrl;
                                $CommentNewModel->author_channel_url = $comment_text->authorChannelUrl;
                                $CommentNewModel->like_count = $comment_text->likeCount;
                                $CommentNewModel->published_at = $newTime;
                                //var_dump($comment->snippet->topLevelComment);
                                $CommentNewModel->Save();

                            }

                        } //foreach ($comments_array as $comment)

                    } //if ($comments_array)

                } //if ($is_filtered_ok)

            }//if ($VideoItemNewModelCount == 0)
            else
            {

                //Если же такое видео уже есть, значит сбрасываем последнюю сканированную страницу на пустоту,
                //чтобы начать сканирование всего канала сначала
                $VideoItemOldModel = YouTuberVideosModel::where('video_id', $CurVideoID)->first();
                $ChannelID = $VideoItemOldModel->channel_id;
                $ChannelModel = YouTuberChannelsModel::where('channel_id', $ChannelID)->first();
                $desc = $ChannelModel->desc;
                if ($desc == 0)
                {
                    $ChannelModel->last_page_token = '';
                    $ChannelModel->Update();
                }
                echo '----------Видео уже есть в базе данных: "' . $title . '" - никаких изменений в бд произведено не было.<BR>';


            }

        }

    }


    /*
     *
     * Получаем массив видео с ютуба
     * https://developers.google.com/youtube/v3/docs/playlistItems/list#try-it
     *
     */
    public function GetVideos($PlayListID, $pageToken)
    {

        $params = array(
            'part' => 'snippet',
            'playlistId' => $PlayListID,
            'maxResults' => 5, //Max = 50
            'pageToken' => $pageToken,
        );

        $Google = new Google();

        //Получаем категории (ID указан выше)
        $obj = $Google->method('playlistItems', $params);

        return $obj;

    }



    /*
     *
     * Получаем статистику по видео
     * https://developers.google.com/youtube/v3/docs/videos/list#User_Uploaded_Videos
     *
     */
    public function GetVideoStat($VideoID)
    {

        $params = array(
            'part' => 'statistics',
            'id' => $VideoID,
        );

        $Google = new Google();

        //Получаем категории (ID указан выше)
        $obj = $Google->method('videos', $params);

        return $obj;

    }





    /*
     *
     * Получаем комментарии ютуба для заданного видео для добавления их в БД
     * https://developers.google.com/youtube/v3/docs/commentThreads/list#try-it
     *
     */
    public function GetComments($VideoID)
    {

        $params = array(
            'part' => 'snippet',
            'videoId' => $VideoID,
            'maxResults' => 100, //Max = 100
            'order' => 'relevance',
        );

        $Google = new Google();

        //Получаем категории (ID указан выше)
        $obj = $Google->method('commentThreads', $params);

        return $obj;

    }


    /*
     *
     * Функция для публикации одного конкретного неопубликованного видео из базы данных в блог
     *
     */
    public function ExportYouTuber1VideoToBlog($VideoID)
    {

        $VideoItemNewModelAll = YouTuberVideosModel::where('video_id', $VideoID)
            ->where('published', 0)
            ->get();

        //Публикуем все неопубликованные видео
        foreach ($VideoItemNewModelAll as $VideoItemNewModel)
        {

            //Находим, к какому каналу принадлежит видео
            $YouTuberChannelsModel = YouTuberChannelsModel::where('channel_id', $VideoItemNewModel->channel_id)->first();

            if (isset($VideoItemNewModel->title))
            {
                $res = $this->PublishVideoInBlog($VideoItemNewModel->title,
                    $VideoItemNewModel->video_id,
                    $YouTuberChannelsModel->channel_caption,
                    $YouTuberChannelsModel->channel_id,
                    $VideoItemNewModel->published_at
                );
                if ($res > -1)
                {
                    $VideoItemNewModel->published = 1;
                    $VideoItemNewModel->post_id = $res;
                    echo '<font color="#006400">Видео ' . $VideoItemNewModel->title . ' успешно опубликовано в блоге!</font><br>';
                    $VideoItemNewModel->Update();
                }
            }

        } //foreach ($VideoItemNewModelAll as $VideoItemNewModel)

    }


    /*
     *
     * Функция для публикации всех неопубликованных видео с включенных каналов из базы данных в блог
     *
     */
    public function ExportYouTuberDataToBlog()
    {
        $YouTuberChannelsModel = YouTuberChannelsModel::where('is_on', 1)->get();

        foreach ($YouTuberChannelsModel as $YouTuberChannelName)
        {

            $ChannelID = $YouTuberChannelName->channel_id;

            $VideoNotPublishedCount = YouTuberVideosModel::where('channel_id', $ChannelID)
                ->where('published', 0)
                ->count();

            //Если есть неопубликованные видео
            if ($VideoNotPublishedCount > 0)
            {

                $VideoItemNewModelAll = YouTuberVideosModel::where('channel_id', $ChannelID)
                    ->where('published', 0)
                    //->orderByRaw("RAND()")
                    ->get();

                //Публикуем все неопубликованные видео
                foreach ($VideoItemNewModelAll as $VideoItemNewModel)
                {

                    if (isset($VideoItemNewModel->title))
                    {

                        $res = $this->PublishVideoInBlog($VideoItemNewModel->title,
                            $VideoItemNewModel->video_id,
                            $YouTuberChannelName->channel_caption,
                            $ChannelID,
                            $VideoItemNewModel->published_at
                        );
                        if ($res > -1)
                        {
                            $VideoItemNewModel->published = 1;
                            $VideoItemNewModel->post_id = $res;
                            echo '<font color="#006400">Видео ' . $VideoItemNewModel->title . ' успешно опубликовано в блоге!</font><br>';
                            $VideoItemNewModel->Update();
                            //Удаляем лишние комменты из базы данных
                            YouTuberCommentsModel::where('video_id', $VideoItemNewModel->video_id)
                                ->delete();
                            echo '<font color="#d2691e">----Для видео ' . $VideoItemNewModel->title . ' успешно очищен кэш комментариев из базы данных</font>.<br>';
                        }

                    }

                } //foreach ($VideoItemNewModelAll as $VideoItemNewModel)

            } //if ($VideoNotPublishedCount > 0)

        } //foreach ($YouTuberChannelsModel as $YouTuberChannelName)

    }


    /*
     *
     * Процедура для публикации информации о видео в блог
     *
     */
    public function PublishVideoInBlog($VideoTitle, $VideoID, $ChannelName, $ChannelID, $VideoDateTime)
    {
        $res = -1;

        $pattern_post_youtuber_arr = file(__DIR__ . '/post_patterns/post_youtuber.txt');
        $pattern_post_youtuber = implode($pattern_post_youtuber_arr); //превращаем в строку
        $pattern_post_youtuber_comments_arr = file(__DIR__ . '/post_patterns/post_youtuber_comments.txt');

        $YouTuberChannelsModelNEW = new YouTuberChannelsModel();

        $YouTuberChannelsID_in = YouTuberChannelsModel::where('channel_id', $ChannelID)->first()->id;

        $pivot_table = $YouTuberChannelsModelNEW->belongsToMany['categories']['table']; //Table for adding ralations between channels and categories
        $cat_id = Db::table($pivot_table)
            ->where('channel_id', $YouTuberChannelsID_in)
            ->pluck('category_id');
        $cat_id = $cat_id[0];

        if (isset($cat_id))
        {

            $title = '"' . $VideoTitle . '" - видео с YouTube-канала "' . $ChannelName . '" ';

            $pattern_post_youtuber = str_replace('$VIDEO_ID', $VideoID, $pattern_post_youtuber);
            $pattern_post_youtuber = str_replace('$LINK_TO_CHANNEL', $title, $pattern_post_youtuber);
            $content = $pattern_post_youtuber;

            //Добавляем в содержание новости комментарии для данного видео
            //$VideoID = 'yTP_BuVVWmI';
            $YouTuberComments = YouTuberCommentsModel::where('video_id', $VideoID)
                ->where('like_count', '>', 2)
                ->OrderBy('like_count', 'desc')
                ->take(10)//берем только первые 10 комментов limit
                ->get();

            //var_dump($YouTuberComments);
            foreach ($YouTuberComments as $YouTuberComment)
            {

                $pattern_post_youtuber_comments = implode($pattern_post_youtuber_comments_arr);
                $pattern_post_youtuber_comments = str_replace('$USER_NAME', $YouTuberComment->author_display_name, $pattern_post_youtuber_comments);
                $pattern_post_youtuber_comments = str_replace('$USER_DATE', $YouTuberComment->published_at, $pattern_post_youtuber_comments);
                $pattern_post_youtuber_comments = str_replace('$COMMENT', $YouTuberComment->comment, $pattern_post_youtuber_comments);
                $pattern_post_youtuber_comments = str_replace('$LIKES', $YouTuberComment->like_count, $pattern_post_youtuber_comments);

                $content .= $pattern_post_youtuber_comments;
            }

            //Добавляем статью в блог: -1 - ошибка, 0 - статья с таким названием уже существует, 1 - статья добавлена
            $addArtToBlog = $this->AddArticleToBlog($title,
                '',
                $content,
                //$this->today,
                $VideoDateTime,
                1,
                $cat_id,
                2);

            $res = $addArtToBlog;

            //Если добавили новость в блог, то добавляем для нее тэги
            if ($addArtToBlog > 0)
            {
                $this->AddTagToPost($addArtToBlog, self::__SITE_NAME);
                $this->AddTagToPost($addArtToBlog, 'Ютуб');
                $this->AddTagToPost($addArtToBlog, $ChannelName); //название канала
            }

        }//if (isset($cat_id))

        return $res;

    }


    /*
     *
     *
     * Метод для удаления из базы данных комментариев тех видео, которые уже опубликованы на сайте
     *
     *
     */
    public function YouTuberRemoveCommentsOfPublishedVideos()
    {

        //Ищем те видео, которые уже опубликованы - можно удалять их комментарии
        $VideosListModel = YouTuberVideosModel::where('published', 1)->get();
        foreach ($VideosListModel as $VideoListItem)
        {
            //Удаляем все, что имеет такой video_id
            if ($VideoListItem->video_id != '')
            {
                YouTuberCommentsModel::where('video_id', $VideoListItem->video_id)
                    ->delete();
                echo 'Для видео "' . $VideoListItem->title . '" [ID=' . $VideoListItem->video_id . '] успешно удалены все комменты из базы данных.<br>';
            }
        }

    }


    /*
     *
     * Проверяем, можно ли публиковать видео с таким названием на сайте (применяем фильтры)
     *
     */
    public function Filter_IsAllowedToPublish($title, $ChannelID)
    {

        $allowed = 1;

        $filters = YouTuberVideosTitleFilterModel::where('channel_id', $ChannelID)
            ->where('is_on', 1)
            ->get();

        if (isset($filters))
        {

            foreach ($filters as $filter)
            {
                $filtered_words = $filter->filtered_words;
                $is_need_allow = $filter->is_allowed;
                $pos = stripos($title, $filtered_words);
                if ($pos === false)
                {
                    if ($is_need_allow) //если нужно, чтобы строка была
                    {
                        $allowed = 0; //если нужная нам строка НЕ найдена в названии ролика, то добавлять НЕ разрешаем
                        //echo 'FILTERED RESTRICTION: '.$title.'<br>';
                    }
                } else
                {
                    if (!$is_need_allow) //если нужно, чтобы строка отсутствовала
                    {
                        $allowed = 0; //если нужная нам строка найдена в названии ролика, но нужно, чтобы её НЕ было
                        //echo 'FILTERED RESTRICTION: '.$title.'<br>';
                    }

                }

            }

        }

        return $allowed;
    }


    /*
     * **********NOT USED
     * Метод для удаления определенного ID видео из списка воспроизведения -
     *
     */
    public function YouTubeRemoveVideoFromTheList($playlist_video_id)
    {

        $Google = new Google();
        $Google->YouTubeRemoveVideoFromTheList($playlist_video_id);
    }




    /*
     *
     * ГЛАВНАЯ ФУНКЦИЯ ЗАПУСКА БОТА "ЮТУБЕР"
     *
     */
    public function Run()
    {

        $this->bot_id = $this->UpdateGeneralInfo(self::__BOT_NAME);

        $this->SaveStatus(1,'Bot Run>>>>', $this->bot_id);

        $Bots = BotModel::where('title', 'YouTuberAddAllBot')->first();

        //$YouTuberImport = new YouTuberImportVideosToWebsite($Bots->last_scanned_id);

        $this->channel_last_id = $Bots->last_scanned_id; //текущий последний канал для сканирования

        $Bots->last_scanned_id = $this->ScanChannels(); //Добавляем в базу данных

        //print_r($Bots->first()->where('title', $this->BotName));
        $this->ExportYouTuberDataToBlog(); //Добавляем из базы данных в блог

        $Bots->Update();

        $this->SaveStatus(1,'<<<<Bot Ended', $this->bot_id);



        //ДОБАВЛЕНИЕ ЮТУБ-РОЛИКОВ В БЛОГ
        /*        for ($i=52; $i <= 52; $i++)
                {
                    $YouTuberImport = new YouTuberImportVideosToWebsite($i);
                    echo 'START';
                    $YouTuberImport->ScanChannels(); //Добавляем в базу данных
                    $YouTuberImport->ExportYouTuberDataToBlog();
                }*/

        // $YouTuberImport->ExportYouTuberDataToBlog();
        //$YouTuberImport->ExportYouTuber1VideoToBlog('KYdGofO3jCg');
        //$YouTuberImport->ScanChannels(); //Добавляем в базу данных
        //print_r($Bots->first()->where('title', $this->BotName));
        //$YouTuberImport->ExportYouTuberDataToBlog(); //Добавляем из базы данных в блог
        //$comments = $YouTuberImport->GetComments('BeSyYgnHuXY');
        //$comments_array = $comments->items;
        //GetComments($VideoID)
        //Указывем ID плейлиста (обязательно к нему должен быть доступ)
        //https://www.youtube.com/playlist?list=PLpztYH-tPct12NnVXuIrK_5Mam687LYsC
        //$YouTuberImport = new YouTuberImportVideosToWebsite();
        //$YouTuberImport->ScanChannels();
        //$YouTuberImport->ExportYouTuberDataToBlog();
        //$YouTuberImport->YouTuberRemoveCommentsOfPublishedVideos();
        //echo var_dump($YouTuberImport->GetVideos());
        //echo var_dump($YouTubeBookmarkBot->GetVideosFromChannel('test'));
        //$client = new \Google_Client();
        //Теперь удаляем видео из плейлиста (будет нужно после того, как настроем публикацию в базу данных, чтобы удалять те видео, которые уже опубликованы)
        //$YouTubeBookmarkBot->YouTubeRemoveVideoFromTheList('UExwenRZSC10UGN0MTJOblZYdUlyS181TWFtNjg3TFlzQy41NkI0NEY2RDEwNTU3Q0M2');

    }


}

