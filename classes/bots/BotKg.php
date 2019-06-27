<?php namespace Cwtuning\Bots\Classes\Bots;

require_once __DIR__.'/../phpQuery-onefile.php';


use Carbon\Carbon;
use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\Article;


/*
 *
 * Бот для сканирования сайта KinoGovno на наличие новых статей
 *
 */
class BotKg extends Bot
{

    const __BOT_NAME = 'kg';
    const __SITE_NAME = 'КГ'; //Отобажаемое название сайта
    protected $title_add = ' - пост с сайта '.self::__SITE_NAME; //добавочка к концу названия поста
    protected $flow_type = ''; //тип потока
    protected $web_site_addr = 'http://kg-portal.ru'; //адрес сайта (вставляется в ссылки и т.д.)


    /*
     *
     * Метод для получения адреса страницы для парсинга в зависимости от выбранной опции (Кино, Сериалы, Игры и Аниме)
     *
     */
    protected function GetMainUrl($page_type)
    {
        switch ($page_type)
        {
            case 1:
                $AddPageType = 'movies'; //Кино
                break;
            case 2:
                $AddPageType = 'tv'; //Сериалы
                break;
            case 3:
                $AddPageType = 'games'; //Игры
                break;
            case 4:
                $AddPageType = 'anime'; //Аниме
                break;
            default:
                $AddPageType = 'movies'; // - По умолчанию
        }
        $Url = $this->web_site_addr.'/news/'.$AddPageType.'/';
        $this->flow_type = $AddPageType;
        return $Url;

    }



    /*
     *
     * Получаем ID категории, в которую мы хотим поместить новость
     *
     */
    protected function GetCatID()
    {
        switch ($this->flow_type)
        {
            case 'movies':
                $CatName = 'movies';
                break;
            case 'tv':
                $CatName = 'movies';
                break;
            case 'games':
                $CatName = 'games';
                break;
            case 'anime':
                $CatName = 'other';
                break;
            default:
                $CatName = 'other';
        }
        return $this->GetCatIDByName($CatName);
    }



    /*
     *
     * Получаем отображаемое по русски название категории, в которую мы хотим поместить новость
     *
     */
    protected function GetFriendlySectionName()
    {
        switch ($this->flow_type)
        {
            case 'movies':
                $CatName = 'кино';
                break;
            case 'tv':
                $CatName = 'сериалы';
                break;
            case 'games':
                $CatName = 'игры';
                break;
            case 'anime':
                $CatName = 'аниме';
                break;
            default:
                $CatName = 'разное';
        }
        return $CatName;
    }






    /*
     *
     * Функция для запуска бота
     *
     */
    public function Run()
    {

        //Инициализация
        $this->bot_id = $this->UpdateGeneralInfo(self::__BOT_NAME);
        $this->SaveStatus(1, 'Bot Run>>>>', $this->bot_id);
        $last_scanned_id = $this->GetBotLastScannedID(self::__BOT_NAME);

        $last_scanned_id = $last_scanned_id + 1;
        if ($last_scanned_id < 1) $last_scanned_id = 1;
        if ($last_scanned_id > 4) $last_scanned_id = 1;

        $this->SetBotLastScannedID(self::__BOT_NAME, $last_scanned_id);

        //1. Сохраняем найденные посты в базу данных для различных разделов источника-сайта
        $this->SaveDataFromMainWebPageToDB($last_scanned_id);

        //2. Публикуем неопубликованные статьи в блоге
        $this->TransferBotArticlesToBlog();

        //Завершение
        $this->SaveStatus(1, '<<<<Bot Ended', $this->bot_id);

    }




    /*
     *
     * Парсинг главной страницы сайта в базу данных
     *
     */
    protected function SaveDataFromMainWebPageToDB($page_type)
    {

        //Получаем Юрл главной страницы для парсинга
        $Url = $this->GetMainUrl($page_type);

        //Скачиваем страницу для парсинга
        $res = $this->DownloadLinkToParse($Url);

        //Получаем массив статей на главной и первоначально заполняем массив $this->RecordPosts[]
        $this->GetAddressesForParsing($res);

        //Сканируем массив на уже опубликованность в БД и если нет, то добавляем в БД bot_articles
        $this->GetDetailsOfPosts();

    }




    /*
     *
     * Метод для получения адресов статей для парсинга (и создания записей каждой из статьи)
     *
     */
    protected function GetAddressesForParsing($WepPageContent)
    {
        $doc = \phpQuery::newDocument($WepPageContent);
        //echo $doc;
        $doc = pq($doc);

        //Получаем ID новости на сайте, чтобы её однозначно идентифицировать
        $this->ParsePq($doc, '.news_output', 'attr', 'id', 'id');

        //Название новости на сайте
        $this->ParsePq($doc, '.news_title', 'text', '', 'title');

        //Ссылка на новость
        $this->ParsePq($doc, '.news_footer > .comments > a', 'attr', 'href', 'link');

        //Краткое содержание новости
        $this->ParsePq($doc, '.news_text', 'html', '', 'excerpt');

        //Количество комментариев
        $this->ParsePq($doc, '.news_footer > .comments > a', 'text', '', 'comments_counter');
    }



    /*
     *
     * Метод для получения содержания каждого поста из массива $this->RecordPosts[]
     *
     */
    protected function GetDetailsOfPosts()
    {

        //ПРОВЕРЯЕМ - ОПУБЛИКОВАНА ЛИ ИСТОРИЯ В БАЗЕ ДАННЫХ И ЕСЛИ НЕТ - ТО ОБРАБАТЫВАЕМ СТРАНИЦУ
        if (isset($this->RecordPosts))
        {

            //Проверяем каждую строку в истории (но не более 2-х за раз - по умолчанию)
            $counter_Posts = 0;
            for ($i=0; $i < count($this->RecordPosts)-1; $i++)
            {

                //Пропускаем новости, у которых ещё не хватает комментов для оценки поста
                if ($this->RecordPosts[$i]->comments_counter < 3) continue;

                $IsAddedAlready = Article::where('story_id', $this->RecordPosts[$i]->id)
                    ->where('bot_name', self::__BOT_NAME)
                    ->count();

                //Если новость ещё не опубликована на сайте
                if (!$IsAddedAlready)
                {


                    $counter_Posts++;

                    //ищем slug - нужен для AJAX-запроса на сервер КГ
                    $text_link = str_replace('/comments/', '', $this->RecordPosts[$i]->link);

                    //Добавляем в заголовок, какой это бот добавил данную статью
                    $this->RecordPosts[$i]->title = $this->RecordPosts[$i]->title . $this->title_add;
                    $this->RecordPosts[$i]->link = $this->web_site_addr.$this->RecordPosts[$i]->link;

                    $Url = $this->RecordPosts[$i]->link.'u_popular/';

                    $res = $this->DownloadLinkToParse($Url); //Скачиваем страницу новости для парсинга
                    $fp = fopen(__DIR__.'/temp/temp_file_url.txt', 'wb');
                    fwrite($fp,$res);
                    fclose($fp);

                    $doc = \phpQuery::newDocument($res);
                    $doc = pq($doc);

                    //Основное содержание новости
                    //Сохраняем основное содержание новости на сайте Кг (с преобразованием гифок и т.д.)
                    //echo '$i='.$i.'<br>';
                    $this->SaveContent($doc, $i);
                    $this->RecordPosts[$i]->content = str_replace('="/', '="'.$this->web_site_addr.'/', $this->RecordPosts[$i]->content);
                    $this->RecordPosts[$i]->content = str_replace('<img ', '<img class="kg-image" ', $this->RecordPosts[$i]->content);
                    $this->RecordPosts[$i]->content = str_replace('<a ', '<a target="_blank" ', $this->RecordPosts[$i]->content);
                    $this->RecordPosts[$i]->content = $this->ApplyViewToContent(
                        $this->RecordPosts[$i]->content,
                        $this->RecordPosts[$i]->link,
                        self::__SITE_NAME
                    );


                    //Получаем рейтинг поста и сохраняем в общий массив
                    $this->GetRating($doc);

                    //Получаем тэги поста и сохраняем в общий массив
                    $this->GetTags($doc);
                    
                    //Получаем ID категории, в которую сохраним пост блога
                    $this->RecordPosts[$i]->cat_id = $this->GetCatID();
                    //echo '$this->RecordPosts[$i]->link='.$this->RecordPosts[$i]->link.'<br>';

                    //Создаем массив тэгов для добавления в БД со статьями
                    $tags = [];
                    foreach ($this->RecordPosts[$i]->tags as $tag_name)
                    {
                        $tags[] = $tag_name;
                    }
                    //Добавляем тэг бота
                    $tags[] = mb_strtolower(self::__SITE_NAME);
                    //Добавляем тэг раздела
                    $tags[] = $this->GetFriendlySectionName();

                    //Добавляем статью в базу данных
                    $ArticleID = $this->AddArticle($this->RecordPosts[$i]->title,
                        $this->RecordPosts[$i]->content,
                        $this->RecordPosts[$i]->content,
                        $this->RecordPosts[$i]->link,
                        $this->RecordPosts[$i]->pluses,
                        $this->RecordPosts[$i]->minuses,
                        $this->RecordPosts[$i]->cat_id,
                        $this->RecordPosts[$i]->id, //StoryID
                        self::__BOT_NAME,
                        $tags
                        );


                    //Находим данные для того, чтобы сделать AJAX-запрос на комментарии
                    $post_id = str_replace('newstop_', '', $this->RecordPosts[$i]->id);
                    $text_link = str_replace('/'.$post_id.'-', '', $text_link);

                    //Теперь сохраняем комментарии для этого поста в базу данных
                    $this->PostSaveCommentsKg($ArticleID,
                        $post_id,
                        $this->RecordPosts[$i]->comments_counter,
                        $text_link);

                } //if ($IsPublished == 0)

                if ($counter_Posts >= $this->max_blog_posts_per_time) break;

            } //for ($i=0; $i < count($this->RecordPosts)-1; $i++)

        } //if (isset($this->RecordPosts))

    }




    /*
     *
     * Метод для извлечения и преобразования контента kg
     *
     */
    protected function SaveContent($doc, $index)
    {

        //Основное содержание новости
        foreach($doc->find('.news_text') as $content)
        {

            $content_pq = pq($content);
            $content_html = $content_pq->html();

            //Если есть видео, то заменяем внутренние тэги сайта на нормальную вставку
            $arr_video = [];
            foreach($content_pq->find('.video') as $data_video)
            {
                $arr_video[] = $data_video;
            }
            for($i=0; $i<count($arr_video); $i++)
            {
               // echo '$index='.$index;
                $data_video = pq($arr_video[$i]);
                $video_url = $data_video->find('script');
                //echo '$video_url='.strip_tags($video_url);
                //находим всё, что между кавычек
                preg_match_all("/([^\"]+)\w([^\"]+)/i", $video_url, $matches);

                //var_dump($matches[0]);
                foreach ($matches[0] as $key => $value)
                {
                    //echo '<br><br><br>$$value='.strip_tags($value);
                    //Перебираем все видео файлы, пока не найдем хорошее качество (последний)
                    $pos = strpos($value, '.mp4');
                    //если это ссылка
                    if ($pos) {
                        $video_url = $value;
                    }
                }
                //echo '$video_url='.$video_url;
                $video_tag_inside = $data_video->html(); //ищем, что заменяем
                //data_video->attr('onclick'); //что заменяем
                //echo $video_tag_inside;
                $pattern_post_kg_video_arr = file(__DIR__.'/post_patterns/post_kg_video.txt'); //паттерн
                $pattern_post_kg_video = implode($pattern_post_kg_video_arr); //превращаем паттерн в строку
                $pattern_post_kg_video = str_replace('$VIDEO_LINK', $video_url, $pattern_post_kg_video);
                //echo $pattern_post_kg_video;
                //В итоге заменяем старые тэги на тэг youtube
                $content_html = str_replace($video_tag_inside, $pattern_post_kg_video, $content_html);
                //var_dump($content_html);
                //echo $this->RecordPosts[$index]->id.'- '.$this->RecordPosts[$index]->title.' - '.$this->RecordPosts[$index]->excerpt.'<br>';

            }
            //echo $content_html;

            //И обновляем содержимое полной новости
            if (isset($this->RecordPosts))
            {
                $this->RecordPosts[$index]->content = $content_html;
                $this->RecordPosts[$index]->excerpt = $content_html;
                //echo '$this->RecordPosts[$i]->content='.$this->RecordPosts[$i]->content.'<br>';
            }
        }

    }





    /*
     *
     * Получаем нормальный формат времени из Хабра
     *
     */
    protected function fix_datetime_kg($dateTime)
    {
        $dateTime = str_replace(' в ', ' ', $dateTime);
        $dateTime = str_replace(' января ', '.01.', $dateTime);
        $dateTime = str_replace(' февраля ', '.02.', $dateTime);
        $dateTime = str_replace(' марта ', '.03.', $dateTime);
        $dateTime = str_replace(' апреля ', '.04.', $dateTime);
        $dateTime = str_replace(' мая ', '.05.', $dateTime);
        $dateTime = str_replace(' июня ', '.06.', $dateTime);
        $dateTime = str_replace(' июля ', '.07.', $dateTime);
        $dateTime = str_replace(' августа ', '.08.', $dateTime);
        $dateTime = str_replace(' сентября ', '.09.', $dateTime);
        $dateTime = str_replace(' октября ', '.10.', $dateTime);
        $dateTime = str_replace(' ноября ', '.11.', $dateTime);
        $dateTime = str_replace(' декабря ', '.12.', $dateTime);
        $year = '.20'; //первая часть текста с годом
        $pos = strpos($dateTime, $year);
        //если года нет в дате, то добавляем текущий год
        if ($pos === false) {
            $dateTime = str_replace('. ', date("Y").' ', $dateTime);
        }
        $cur_date = date_parse_from_format('j.m.Y H:i',$dateTime);
        $cur_timestamp = mktime($cur_date['hour'],
                                $cur_date['minute'],
                                $cur_date['second'],
                                $cur_date['month'],
                                $cur_date['day'],
                                $cur_date['year']
            );
        $dateTime = date('Y-m-d H:i:s', $cur_timestamp);
        return $dateTime;
    }



    /*
     *
     * Отдельная функция для сохранения комментов из ответа на AJAX-запроса самых популярных комментов
     *
     */
    public function PostSaveCommentsKg($ArticleID,
                                          $post_id,
                                          $comments_number,
                                          $text_link
                                    )
    {
        $url_to_parse = $this->web_site_addr.'/useractions.php?action=comments_load&id='.$post_id.'&type=news&page=1&numcomments='.$comments_number.'&user=popular&textlink='.$text_link;
        //echo $url_to_parse;
        $res = $this->DownloadLinkToParse($url_to_parse); //Делаем запрос AJAX для парсинга
        $res = json_decode($res);
        $res = $res->html;
        $doc = \phpQuery::newDocument($res);
        $doc = pq($doc);


        $k = 0;
        foreach($doc->find('li.comment') as $content)
        {
            $comment_pq = pq($content);

            //Parent ID
            //$parent_id = $comment_pq->find('.parent_id:eq(0)')->attr('data-parent_id');
            //if ($parent_id > 0) continue; //если это не родитель, то пропускаем ход

            //Comment ID
            $comment_id = $comment_pq->attr('id');

            //Картинка пользователя
            $comment_author_img = $comment_pq->find('.avatar > a > img')
                ->attr('src');

            //Рейтинг
            $comment_rating = $comment_pq->find('.karma')
                ->text();
            $comment_rating = intval($comment_rating);

            //Имя пользователя
            $comment_author_name = $comment_pq->find('.user > .user_name')
                ->text();

            //Юрл до профиля пользователя
            $comment_author_url = $comment_pq->find('.user > .user_name')
                ->attr('href');
            $comment_author_url = $this->web_site_addr.$comment_author_url;

            //Время коммента
            $comment_timestamp = $comment_pq->find('.status > .user_name')
                ->text();
            $comment_datetime = $this->fix_datetime_kg($comment_timestamp);

            //Ссылка на коммент
            $comment_link = $comment_pq->find('.status > .user_name')
                ->attr('href');
            $comment_link = $this->web_site_addr.$comment_link;

            //Сам коммент
            $story_comment = $comment_pq->find('.body > p')
                ->html();

            /*For testing pruposes
             * echo '<br>$comment_id='.$comment_id;
            echo '<br>$comment_author_img='.$comment_author_img;
            echo '<br>$comment_rating='.$comment_rating;
            echo '<br>$comment_author_name='.$comment_author_name;
            echo '<br>$comment_author_url='.$comment_author_url;
            echo '<br>$comment_datetime='.$comment_datetime;
            echo '<br>$comment_link='.$comment_link;
            echo '<br>$story_comment='.$story_comment;
            dd();
            */

            ////Добавляем коммент в БД (если его нет, конечно ещё в БД)
            $this->AddCommentToDb($ArticleID, $comment_id, $story_comment, $comment_author_name,
                $comment_author_img, $comment_author_url, $comment_rating,$comment_datetime,$comment_link);

            $k++;


            if ($k > $this->max_comments_in_db) break; //ограничение на 40 комментариев максимум - для защиты от перегрузки сервера

        }

    }




    /*
     *
     * Метод для нахождения рейтинга поста
     *
     */
    protected function GetRating($doc)
    {

        $post_id = -1;

        //Ищем ID поста
        foreach($doc->find('.news_output') as $item)
        {
            $item = pq($item);
            $post_id = $item->attr("id"); //ID поста - newstop_67177
        }

        //Ищем количество поделившихся
        foreach($doc->find('.news_footer > .sharing > .label > b') as $item)
        {
            $item = pq($item);
            $text = $item->text();
            foreach ($this->RecordPosts as $itemIn)
            {
                if ($itemIn->id == $post_id)
                {
                    $itemIn->pluses = $text;
                    $itemIn->minuses = 0;
                }
            }
            break;
        }

    }



    /*
     *
     * Метод для поиска тэгов поста
     *
     */
    protected function GetTags($doc)
    {

        $post_id = -1;

        //Ищем ID поста
        foreach($doc->find('.news_output') as $item)
        {
            $item = pq($item);
            $post_id = $item->attr("id"); //ID поста - newstop_67177
        }

        //Ищем все тэги и добавляем их
        foreach($doc->find('.tags > .list > a') as $item)
        {
            $item = pq($item);
            $text = $item->text(); //получаем тэг
            foreach ($this->RecordPosts as $itemIn)
            {
                if ($itemIn->id == $post_id)
                {
                    $itemIn->tags[] = $text;
                }
            }
        }

    }










}