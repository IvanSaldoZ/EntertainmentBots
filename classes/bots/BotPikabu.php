<?php namespace Cwtuning\Bots\Classes\Bots;

require_once __DIR__.'/../phpQuery-onefile.php';



use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\Bot as BotModel;

use Cwtuning\Bots\Models\Article as ArticleModel;


use Carbon\Carbon;


/*
 *
 * Бот для добавления видео с плейлиста ютуба в базу данных
 *
 */
class BotPikabu extends Bot
{

    const __BOT_NAME = 'BotPikabu';
    const __SITE_NAME = 'Пикабу'; //Отобажаемое название сайта
    protected $title_add = ' - пост с сайта '.self::__SITE_NAME; //добавочка к концу названия поста

    protected $comment_rating_minimum = 2; //минимальный рейтинг коммента, когда он ещё добавляется в БД для публикации в моем блоге




    /*
     *
     * Точка входа для главной страницы Пикабу и сканирования всех адресов в массив, а затем - публикация в БД страницы в базу данных
     *
     */
    public function SaveDataFromPikabuToDB($is_hot, $sort_type=0)
    {

        if ($is_hot) $page_str = 'hot'; else $page_str = 'best';
        $Url = 'https://pikabu.ru/'.$page_str;
        $this->ScanAddrAndSaveToDb($Url,$sort_type);

    } //public function SaveDataFromPikabuToDB()





    /*
     *
     * Функция сканирования и добавления конкретной новости или даже всей главной страницы в базу данных
     *
     */
    public function ScanAddrAndSaveToDb($Url, $sort_type=0)
    {
        switch ($sort_type)
        {
            case 1:
                $AddSortType = '?f=1#story-info'; //Сортировать комментарии внутри поста - По рейтингу
                break;
            case 2:
                $AddSortType = '?f=2#story-info'; // - По времени
                break;
            default:
                $AddSortType = ''; // - По актуальности
        }

        $res = $this->DownloadLinkToParse($Url); //Скачиваем страницу для парсинга

        $doc = \phpQuery::newDocument($res);
        //echo $doc;
        $doc = pq($doc);

        $i = 0;

        //Получаем ID новости на сайте Пикабу, чтобы её однозначно идентифицировать
        foreach($doc->find('.story') as $story_id)
        {
            $story_id = pq($story_id);
            $story_caption = $story_id->attr('data-story-id');
            $this->RecordPosts[] = new Records\RecordPost();
            $this->RecordPosts[$i]->id = $story_caption;
            // echo '$this->RecordPosts[$i]->id='.$this->RecordPosts[$i]->id.'<br>';
            $i++;
        }


        $i = 0;
        //Название новости на сайте Пикабу и ссылка на новость
        foreach($doc->find('.story__title-link') as $name_result)
        {
            $name_result = pq($name_result);
            $title_caption = $name_result->text();
            $link_to_news = $name_result->attr('href');
            if (isset($this->RecordPosts))
            {
                $this->RecordPosts[$i]->title = $title_caption;
                $this->RecordPosts[$i]->link = $link_to_news;
                //echo '$this->RecordPosts[$i]->title='.$this->RecordPosts[$i]->title.'<br>';
                //echo '---------$this->RecordPosts[$i]->link='.$this->RecordPosts[$i]->link.'<br>';
                $i++;
            }
        }


        $i = 0;
        //Получаем Timestamp новости
        foreach($doc->find('.story__date') as $datetime)
        {
            $datetime = pq($datetime);
            $datetime = $datetime->attr('title');
            if (isset($this->RecordPosts))
            {
                $dt = Carbon::createFromTimestamp($datetime);
                $this->RecordPosts[$i]->timestamp = $dt->toDateTimeString();
                //echo '$this->RecordPosts[$i]->timestamp='.$this->RecordPosts[$i]->timestamp.'<br>';
                $i++;
            }
        }


        $i = 0;
        //Получаем автора новости (если ads - то это реклама)
        foreach($doc->find('.story__author') as $author_name)
        {
            $author_name = pq($author_name);
            $author_name = $author_name->text();
            if (isset($this->RecordPosts))
            {
                $this->RecordPosts[$i]->author_name = $author_name;
                $i++;
            }
        }

        //ПРОВЕРЯЕМ - ОПУБЛИКОВАНА ЛИ ИСТОРИЯ В БАЗЕ ДАННЫХ И ЕСЛИ НЕТ - ТО ПУБЛИКУЕМ!
        if (isset($this->RecordPosts))
        {

            //Проверяем каждую строку в истории (но не более 2-х за раз)
            $counter_PkPosts = 0;
            for ($i=0; $i < count($this->RecordPosts)-1; $i++)
            {

                $StoryID = $this->RecordPosts[$i]->id;
                $author_name = $this->RecordPosts[$i]->author_name; //если ads - значит, реклама
                //OLD___$PikabuStoryModel_counter = PikabuStoryModel::where('story_id', $StoryID)
                $ArticleModel_counter = ArticleModel::where('story_id', $StoryID)
                    ->where('bot_name', self::__BOT_NAME)
                    ->count();
                //echo $i.'<br>';
                //echo $this->RecordPosts[$i]->id.'<br>';

                //Если новость ещё не опубликована на сайте
                if (($ArticleModel_counter == 0) AND ($StoryID > 0) AND ($author_name != 'ads'))
                {

                    $counter_PkPosts++;

                    $Url = $this->RecordPosts[$i]->link;
                    // echo '$Url='.$Url.'<br>';
                    $Url = $Url.$AddSortType; //Добавляем тип сортировки комментариев

                    $res = $this->DownloadLinkToParse($Url); //Скачиваем страницу новости для парсинга
                    $doc = \phpQuery::newDocument($res);
                    $doc = pq($doc);

                    //Сохраняем основное содержание новости на сайте Пикабу (с преобразованием гифок и т.д.)
                    $this->SaveContent($doc, $i);

                    //Ищем количество плюсиков и минусиков
                    $this->GetRatingOfAnStory($doc, $i);

                    //Ищем тэги
                    $tags = $this->GetTagsOfAnStory($doc);

                    //Сохраняем категорию
                    $cat_id = $this->GetBotCatID(self::__BOT_NAME);
                    $this->RecordPosts[$i]->cat_id = $cat_id;

                    //изменяем заголовок и делаем содержание в соответствие с видом
                    $this->RecordPosts[$i]->title = $this->RecordPosts[$i]->title . $this->title_add;
                    $this->RecordPosts[$i]->content = $this->ApplyViewToContent(
                        $this->RecordPosts[$i]->content,
                        $this->RecordPosts[$i]->link,
                        self::__SITE_NAME
                    );

                    //Добавляем статью в базу данных
                    $ArticleID = $this->AddArticle($this->RecordPosts[$i]->title,
                        '',
                        $this->RecordPosts[$i]->content,
                        $this->RecordPosts[$i]->link,
                        $this->RecordPosts[$i]->pluses,
                        $this->RecordPosts[$i]->minuses,
                        $this->RecordPosts[$i]->cat_id,
                        $this->RecordPosts[$i]->id,
                        self::__BOT_NAME,
                        $tags
                    );

                    //Теперь сохраняем комментарии для этого поста в базу данных
                    if ($ArticleID > -1)
                    {
                        $this->SaveCommentsForPikabuPost($ArticleID, $doc);
                    }

                } //if ($PikabuStoryModel_counter == 0)

                if ($counter_PkPosts >= $this->max_blog_posts_per_time) break;

            } //for ($i=0; $i < count($this->RecordPosts); $i++)

        } //if (isset($this->RecordPosts))

    }



    /*
     *
     * Метод для извлечения и преобразования контента Пикабу
     *
     */
    protected function SaveContent($doc, $index)
    {

        //Основное содержание новости на сайте Пикабу
        foreach($doc->find('.b-story__content') as $content)
        {

            $content_pq = pq($content);
            $content_html = $content_pq->html();

            //Если есть видео, то заменяем внутренние тэги Пикабу на вставку Youtub-а
            $arr_video = [];
            foreach($content_pq->find('.b-video') as $data_video)
            {
                $arr_video[] = $data_video;
            }
            for($i=0; $i<count($arr_video); $i++)
            {
                $data_video = pq($arr_video[$i]);
                $video_url = $data_video->attr('data-url');
                $video_tag_inside = $data_video->html(); //что заменяем
                $pattern_post_pikabu_video_arr = file(__DIR__.'/post_patterns/post_pikabu_video.txt'); //паттерн
                $pattern_post_pikabu_video = implode($pattern_post_pikabu_video_arr); //превращаем паттерн в строку
                $pattern_post_pikabu_video = str_replace('$VIDEO_LINK', $video_url, $pattern_post_pikabu_video);
                //echo $pattern_post_pikabu_video;
                //В итоге заменяем старые тэги на тэг youtube
                $content_html = str_replace($video_tag_inside, $pattern_post_pikabu_video, $content_html);
            }

            //Если есть гифка, то заменяем внутренние тэги Пикабу на гифку
            $arr_gif = [];
            foreach($content_pq->find('.b-gifx') as $data_gif)
            {
                $arr_gif[] = $data_gif;
            }
            //$is_gif = $content_pq->find('.b-gifx')->attr('data-type');
            for($i=0; $i<count($arr_gif); $i++)
            {
                $data_gif = pq($arr_gif[$i]);
                $gif_tag_inside = $data_gif->html(); //что заменяем
                $gif_url = $data_gif->find('.b-gifx__player')->attr('data-src'); //путь до гифки
                $gif_tag = '<img src="'.$gif_url.'">';
                //В итоге заменяем старые тэги на гифку
                $content_html = str_replace($gif_tag_inside, $gif_tag, $content_html);
            }

            if (isset($this->RecordPosts))
            {
                $this->RecordPosts[$index]->content = $content_html;
                //echo '$this->RecordPosts[$i]->content='.$this->RecordPosts[$i]->content.'<br>';
            }

        }

    }



    /*
     *
     * Ищем рейтинг статьи
     *
     */
    protected function GetRatingOfAnStory($doc, $index)
    {
        foreach($doc->find('.b-story__rating') as $content)
        {
            $content = pq($content);
            $story_pluses = $content->attr("data-pluses");
            $story_minuses = $content->attr("data-minuses");
            if (isset($this->RecordPosts))
            {
                $this->RecordPosts[$index]->pluses = $story_pluses;
                $this->RecordPosts[$index]->minuses = $story_minuses;
                //echo '$this->RecordPosts[$i]->pluses='.$this->RecordPosts[$i]->pluses.'<br>';
                //echo '$this->RecordPosts[$i]->minuses='.$this->RecordPosts[$i]->minuses.'<br>';
            }
        }
    }




    /*
     *
     * Ищем тэги для статьи
     *
     */
    protected function GetTagsOfAnStory($doc)
    {
        $tags = [];
        foreach($doc->find('.story__tag') as $content)
        {
            $content = pq($content);
            $tag = $content->text();
            $tags[] = $tag; //добавляем тэг в массив
        }
        //Добавляем тэг бота
        $tags[] = mb_strtolower(self::__SITE_NAME);
        return $tags;

    }




    /*
     *
     * Отдельная функция для сохранения комментов из текста поста на пикабу (парсинг из текста)
     *
     */
    protected function SaveCommentsForPikabuPost($ArticleID, $doc)
    {

        $k = 0;
        foreach($doc->find('.b-comment[data-parent-id="0"]') as $content)
        {

            $comment_pq = pq($content);

            //Comment ID
            $comment_id = $comment_pq->attr('data-id');

            //Рейтинг
            $comment_rating = $comment_pq->find('.b-comment__rating-count:eq(0)')
                ->text();

            //Имя пользователя
            $comment_author_name = $comment_pq->find('.b-comment__user:eq(0) > a')
                ->text();

            //Картинка пользователя
            $comment_author_img = $comment_pq->find('.b-comment__user:eq(0) > a > img')
                ->attr('src');

            //убираем лишний пробел в начале имени (это так получается у тех, у кого есть аватар)
            if ($comment_author_img != '')
            {
                //http://iwsm.ru/blog/show/kak-udality-pervie-1-2-3-n-simvolov-v-stroke-na-php
                $comment_author_name = mb_substr($comment_author_name, 1);
            }

            //Юрл до профиля пользователя
            $comment_author_url = $comment_pq->find('.b-comment__user:eq(0) > a')
                ->attr('href');

            //Время коммента
            $comment_timestamp = $comment_pq->find('.b-comment__time:eq(0)')
                ->attr('datetime');
            $comment_datetime = Carbon::createFromTimestamp($comment_timestamp)->toDateTimeString();

            //Ссылка на коммент
            $comment_link = $comment_pq->find('.b-comment__tool-link:eq(0) > a')
                ->attr('href');

            //Сам коммент
            $story_comment = $comment_pq->find('.b-comment__content:eq(0)')
                ->html();
            $story_comment = $this->Pikabu_FixComment($story_comment); //делаем так, чтобы было видно гифки и т.д. в комментариях

            //Добавляем коммент в БД (если его нет, конечно ещё в БД)
            $this->AddCommentToDb($ArticleID, $comment_id, $story_comment, $comment_author_name,
                $comment_author_img, $comment_author_url, $comment_rating,$comment_datetime,$comment_link);

            $k++;
            if ($k > $this->max_comments_in_db) break; //ограничение на 40 комментариев максимум - для защиты от перегрузки сервера

        }

    }



    /*
     *
     * Обрабатываем содержание комментов Пикабу (делаем гифку - гифкой и т.д.)
     *
     */
    protected function Pikabu_FixComment($comment_text)
    {
        $doc = \phpQuery::newDocument($comment_text);
        $doc = pq($doc);
        //Если есть гифка, то заменяем внутренние тэги Пикабу на гифку
        $arr_gif = [];
        foreach($doc->find('.b-gifx') as $data_gif)
        {
            $arr_gif[] = $data_gif;
        }
        //$is_gif = $content_pq->find('.b-gifx')->attr('data-type');
        for($i=0; $i<count($arr_gif); $i++)
        {
            $data_gif = pq($arr_gif[$i]);
            $gif_tag_inside = $data_gif->html(); //что заменяем
            $gif_url = $data_gif->find('.b-gifx__player')->attr('data-src'); //путь до гифки
            $gif_tag = '<img src="'.$gif_url.'">';
            //В итоге заменяем старые тэги на гифку
            $comment_text = str_replace($gif_tag_inside, $gif_tag, $comment_text);
        }
        return $comment_text;
    }




    /*
     * ГЛАВНАЯ ФУНКЦИЯ ЗАПУСКА БОТА "ПИКАБУ"
     */
    public function Run()
    {

        $this->bot_id = $this->UpdateGeneralInfo(self::__BOT_NAME);
        $this->SaveStatus(1,'Bot Run>>>>', $this->bot_id);
        $Bots = BotModel::where('title', self::__BOT_NAME)->first();
        $last_scanned_id = $Bots->last_scanned_id;
        if ($last_scanned_id == 0) $last_scanned_id = 1; else $last_scanned_id = 0;
        //$BotPikabu = new BotPikabu();

        //1. Сохраняем найденные статьи в БД для различных разделов Хабра
        $this->SaveDataFromPikabuToDB($last_scanned_id,0);

        //2. Публикуем неопубликованные статьи в блоге
        $this->TransferBotArticlesToBlog();

        $Bots->last_scanned_id = $last_scanned_id;
        $Bots->Update();
        $this->SaveStatus(1,'<<<<Bot Ended', $this->bot_id);

    }







}
