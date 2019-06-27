<?php namespace Cwtuning\Bots\Classes\Bots;

require_once __DIR__.'/../phpQuery-onefile.php';



use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\Article;


/*
 *
 * Бот для сканирования сайта на наличие новых статей и публикации их в заданном блоге
 *
 */
class BotFishkiNet extends Bot
{

    const __BOT_NAME = 'FishkiNet';
    const __SITE_NAME = 'Фишки.Net'; //Отобажаемое название сайта
    protected $title_add = ' - пост с сайта '.self::__SITE_NAME; //добавочка к концу названия поста
    protected $flow_type = ''; //тип потока
    protected $web_site_addr = 'http://fishki.net'; //адрес сайта (вставляется в ссылки и т.д.)




    /*
     *
     * Получаем ID категории, в которую мы хотим поместить новость
     *
     */
    protected function GetCatID()
    {
        return $this->GetBotCatID(self::__BOT_NAME);
    }




    /*
     *
     * Метод для получения адреса главной страницы для парсинга в зависимости от потока
     *
     */
    protected function GetMainUrl($page_type)
    {
        switch ($page_type)
        {
            case 1:
                $AddPageType = 'gif'; //GIF
                break;
            case 2:
                $AddPageType = 'gif'; //GIF
                break;
            case 3:
                $AddPageType = 'gif'; //GIF
                break;
            case 4:
                $AddPageType = 'gif'; //GIF
                break;
            case 5:
                $AddPageType = 'gif'; //GIF
                break;
            default:
                $AddPageType = 'gif'; // - По умолчанию - GIF
        }
        $Url = 'http://fishki.net/'.$AddPageType.'/';
        $this->flow_type = $AddPageType;
        return $Url;
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
        $this->ParsePq($doc, '.drag_element > .expanded-post', 'attr', 'data-post-id', 'id');

        //Название новости на сайте
        $this->ParsePq($doc, '.content__title > a', 'text', '', 'title');

        //Ссылка на новость
        $this->ParsePq($doc, '.content__title > a', 'attr', 'href', 'link');

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
        foreach($doc->find('.post-wrap') as $item)
        {
            $item = pq($item);
            $post_id = $item->attr("data-post-id"); //ID поста - data-post-id="2386228"
        }
        //Ищем все тэги и добавляем их
        foreach($doc->find('.preview_tag:eq(0) > a') as $item) //:gt(0) - получаем индексы, больше 0 (пропускаем нулевой, потому что это - не тэг) - https://code.google.com/archive/p/phpquery/wikis/Selectors.wiki
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



    /*
     *
     * Метод для нахождения рейтинга поста
     *
     */
    protected function GetRating($doc)
    {

        $post_id = -1;

        //Ищем ID поста
        foreach($doc->find('.post-wrap') as $item)
        {
            $item = pq($item);
            $post_id = $item->attr("data-post-id"); //ID поста - data-post-id="2386228"
        }
        //Ищем количество тех, кто поставил плюс
        foreach($doc->find('.likes-count--big') as $item)
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

                $IsAddedAlready = Article::where('story_id', $this->RecordPosts[$i]->id)
                    ->where('bot_name', self::__BOT_NAME)
                    ->count();

                //Если новость ещё не опубликована на сайте
                if (!$IsAddedAlready)
                {

                    $counter_Posts++;

                    //Добавляем в заголовок, какой это бот добавил данную статью
                    $this->RecordPosts[$i]->title = trim($this->RecordPosts[$i]->title . $this->title_add);

                    $this->RecordPosts[$i]->link = $this->web_site_addr.$this->RecordPosts[$i]->link;
                    $Url = $this->RecordPosts[$i]->link;

                    $res = $this->DownloadLinkToParse($Url); //Скачиваем страницу новости для парсинга
                    $fp = fopen(__DIR__.'/temp/temp_file_url.txt', 'wb');
                    fwrite($fp,$res);
                    fclose($fp);
                    $doc = \phpQuery::newDocument($res);
                    $doc = pq($doc);


                    //Основное содержание новости
                    $this->ParsePq($doc, '.gif-animated', 'html', '', 'content', $i);
                    $this->RecordPosts[$i]->content = str_replace('data-src=', 'src=', $this->RecordPosts[$i]->content);
                    $this->RecordPosts[$i]->content = $this->ApplyViewToContent(
                        $this->RecordPosts[$i]->content,
                        $this->RecordPosts[$i]->link,
                        self::__SITE_NAME
                    );
                    $this->RecordPosts[$i]->excerpt = '';

                    //Получаем тэги поста и сохраняем в общий массив
                    $this->GetTags($doc);

                    //Получаем рейтинг поста и сохраняем в общий массив
                    $this->GetRating($doc);

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

                    //var_dump($this->RecordPosts[$i]);
                    //dd();

                    //Добавляем статью в базу данных
                    $ArticleID = $this->AddArticle($this->RecordPosts[$i]->title,
                        $this->RecordPosts[$i]->excerpt,
                        $this->RecordPosts[$i]->content,
                        $this->RecordPosts[$i]->link,
                        $this->RecordPosts[$i]->pluses,
                        $this->RecordPosts[$i]->minuses,
                        $this->RecordPosts[$i]->cat_id,
                        $this->RecordPosts[$i]->id, //StoryID
                        self::__BOT_NAME,
                        $tags
                        );

                    //Теперь сохраняем комментарии для этого поста в базу данных
                    $this->PostSaveComments($ArticleID, $doc, $this->RecordPosts[$i]->link);

                } //if ($IsPublished == 0)

                if ($counter_Posts >= $this->max_blog_posts_per_time) break;

            } //for ($i=0; $i < count($this->RecordPosts)-1; $i++)

        } //if (isset($this->RecordPosts))

    }




    /*
     *
     * Отдельная функция для сохранения комментов из текста поста
     *
     */
    protected function PostSaveComments($ArticleID, $doc, $link='')
    {
        $k = 0;

        foreach($doc->find('.comments-thread') as $content)
        {

            $comment_pq = pq($content);

            //Comment ID
            $comment_id = $comment_pq->find('.comment:eq(0)')->attr('id');

            //Рейтинг
            $comment_rating = $comment_pq->find('.likes-count:eq(0)')
                ->text();
            $comment_rating = intval($comment_rating);

            //Имя пользователя
            $comment_author_name = $comment_pq->find('.comment__meta__name:eq(0)')
                ->text();

            //Картинка пользователя
            $comment_author_img = $comment_pq->find('.root-img:eq(0)')
                ->attr('src');

            //Юрл до профиля пользователя
            $comment_author_url = $comment_pq->find('.avatar-a:eq(0)')
                ->attr('href');
            $comment_author_url = $this->web_site_addr . $comment_author_url;

            //Время коммента
            $comment_timestamp = $comment_pq->find('.comment__meta__info:eq(0)')
                ->attr('data-rel');
            $comment_datetime = date('Y-m-d H:i:s', $comment_timestamp);

            //Ссылка на коммент
            $comment_link = $comment_pq->find('.comment-link:eq(0)')
                ->attr('href');

            //Сам коммент
            $story_comment = $comment_pq->find('.comment__text:eq(0)')
                ->html();

            //For testing pruposes
            /*            echo '<br>$comment_id='.$comment_id;
            echo '<br>$comment_author_img='.$comment_author_img;
            echo '<br>$comment_rating='.$comment_rating;
            echo '<br>$comment_author_name='.$comment_author_name;
            echo '<br>$comment_author_url='.$comment_author_url;
            echo '<br>$comment_datetime='.$comment_datetime;
            echo '<br>$comment_link='.$comment_link;
            echo '<br>$story_comment='.$story_comment;*/

            //Добавляем коммент в БД (если его нет, конечно ещё в БД)
            $this->AddCommentToDb($ArticleID, $comment_id, $story_comment, $comment_author_name,
                $comment_author_img, $comment_author_url, $comment_rating,$comment_datetime,$comment_link);

            $k++;

            if ($k > $this->max_comments_in_db) break; //ограничение на 40 комментариев максимум - для защиты от перегрузки сервера

        }

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
        if ($last_scanned_id > 5) $last_scanned_id = 1;
        $this->SetBotLastScannedID(self::__BOT_NAME, $last_scanned_id);

        //1. Сохраняем найденные статьи в БД для различных разделов сайта
        $this->SaveDataFromMainWebPageToDB($last_scanned_id);

        //2. Публикуем неопубликованные статьи в блоге
        $this->TransferBotArticlesToBlog();

        //Завершение
        $this->SaveStatus(1, '<<<<Bot Ended', $this->bot_id);

    }



}