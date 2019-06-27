<?php namespace Cwtuning\Bots\Classes\Bots;

require_once __DIR__.'/../phpQuery-onefile.php';



use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\Article;


/*
 *
 * Бот для сканирования сайта Habrahabr на наличие новых статей
 *
 */
class BotHabrahabr extends Bot
{

    const __BOT_NAME = 'Habrahabr';
    const __SITE_NAME = 'Хабрахабр'; //Отобажаемое название сайта
    protected $title_add = ' - пост с сайта '.self::__SITE_NAME; //добавочка к концу названия поста
    protected $flow_type = ''; //тип потока
    //const __TAG_NAME = 'хабрахабр'; //Название тэга, которое всегда будет прикрепляться к новости от данного бота


    /*
     *
     * Метод для получения адреса главной страницы для парсинга в зависимости от потока (Разработка, Администрирование, Дизайн и т.д.)
     *
     */
    protected function GetMainUrl($page_type)
    {
        switch ($page_type)
        {
            case 1:
                $AddPageType = 'develop'; //Разработка
                break;
            case 2:
                $AddPageType = 'admin'; //Администрирование
                break;
            case 3:
                $AddPageType = 'design'; //Дизайн
                break;
            case 4:
                $AddPageType = 'management'; //Управление
                break;
            case 5:
                $AddPageType = 'marketing'; //Маркетинг
                break;
            case 6:
                $AddPageType = 'misc'; //Разное
                break;
            default:
                $AddPageType = 'develop'; // - По умолчанию - разработка
        }
        $Url = 'https://habrahabr.ru/flows/'.$AddPageType.'/top10/';
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
            case 'develop':
                $CatName = 'development';
                break;
            case 'admin':
                $CatName = 'web';
                break;
            case 'design':
                $CatName = 'development';
                break;
            case 'management':
                $CatName = 'business';
                break;
            case 'marketing':
                $CatName = 'business';
                break;
            case 'misc':
                $CatName = 'other';
                break;
            default:
                $CatName = 'other';
        }
        return $this->GetCatIDByName($CatName);
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
        $this->ParsePq($doc, '.js-post-vote', 'attr', 'data-id', 'id');

        //Название новости на сайте
        $this->ParsePq($doc, '.post__title_link', 'text', '', 'title');

        //Ссылка на новость
        $this->ParsePq($doc, '.post__title_link', 'attr', 'href', 'link');

        //Краткое содержание новости
        $this->ParsePq($doc, '.post__text', 'html', '', 'excerpt');
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
        foreach($doc->find('.js-post-vote') as $item)
        {
            $item = pq($item);
            $post_id = $item->attr("data-id"); //ID поста - 268539
        }

        //Ищем количество плюсиков и минусиков
        foreach($doc->find('.voting-wjt__counter') as $item)
        {
            $item = pq($item);
            $text = $item->attr("title"); //Общий рейтинг 20: ↑20 и ↓0
            $text = str_replace('↑', '', $text);
            $text = str_replace('↓', '', $text);
            $pieces = explode(": ", $text);
            $text = $pieces[1]; // 20 и 0
            $pieces2 = explode(" и ", $text); //20; 0
            $story_pluses = intval($pieces2[0]);
            $story_minuses = intval($pieces2[1]);
            foreach ($this->RecordPosts as $itemIn)
            {
                if ($itemIn->id == $post_id)
                {
                    $itemIn->pluses = $story_pluses;
                    $itemIn->minuses = $story_minuses;
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
        foreach($doc->find('.js-post-vote') as $item)
        {
            $item = pq($item);
            $post_id = $item->attr("data-id"); //ID поста - 268539
        }

        //Ищем все тэги и добавляем их
        foreach($doc->find('.hub-link') as $item)
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
                    $this->RecordPosts[$i]->title = $this->RecordPosts[$i]->title . $this->title_add;

                    $Url = $this->RecordPosts[$i]->link;

                    $res = $this->DownloadLinkToParse($Url); //Скачиваем страницу новости для парсинга
                    $doc = \phpQuery::newDocument($res);
                    $doc = pq($doc);

                    //Основное содержание новости
                    $this->ParsePq($doc, '.post__text', 'html', '', 'content', $i);
                    $this->RecordPosts[$i]->content = $this->ApplyViewToContent(
                        $this->RecordPosts[$i]->content,
                        $this->RecordPosts[$i]->link,
                        self::__SITE_NAME
                    );

                    //Добавляем также ссылку на источник для excerpt-а
                    /*
                     * $this->RecordPosts[$i]->excerpt = $this->ApplyViewToContent(
                        $this->RecordPosts[$i]->excerpt,
                        $this->RecordPosts[$i]->link,
                        self::__SITE_NAME
                    );
                    */

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
     * Получаем нормальный формат времени из Хабра
     *
     */
    protected function fix_datetime_habrahabr($dateTime)
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
     * Отдельная функция для сохранения комментов из текста поста
     *
     */
    protected function PostSaveComments($ArticleID, $doc, $link='')
    {
        $k = 0;
        foreach($doc->find('.content-list__item_comment') as $content)
        {

            $comment_pq = pq($content);

            //Parent ID
            $parent_id = $comment_pq->find('.parent_id:eq(0)')->attr('data-parent_id');
            if ($parent_id > 0) continue; //если это не родитель, то пропускаем ход

            //Comment ID
            $comment_id = $comment_pq->find('.comment__head:eq(0)')->attr('rel');

            //Рейтинг
            $comment_rating = $comment_pq->find('.voting-wjt__counter:eq(0)')
                ->text();
            $comment_rating = intval($comment_rating);

            //Имя пользователя
            $comment_author_name = $comment_pq->find('.user-info__nickname_comment:eq(0)')
                ->text();

            //Картинка пользователя
            $comment_author_img = $comment_pq->find('.user-info__image-pic:eq(0)')
                ->attr('src');

            //Юрл до профиля пользователя
            $comment_author_url = $comment_pq->find('.user-info_inline:eq(0)')
                ->attr('href');

            //Время коммента
            $comment_timestamp = $comment_pq->find('.comment__date-time:eq(0)')
                ->text();
            $comment_datetime = $this->fix_datetime_habrahabr($comment_timestamp);

            //Ссылка на коммент
            $comment_link = $comment_pq->find('.icon_comment-anchor:eq(0)')
                ->attr('href');
            $comment_link = $link.$comment_link;

            //Сам коммент
            $story_comment = $comment_pq->find('.comment__message:eq(0)')
                ->html();

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
        if ($last_scanned_id > 6) $last_scanned_id = 1;
        $this->SetBotLastScannedID(self::__BOT_NAME, $last_scanned_id);

        //1. Сохраняем найденные статьи в БД для различных разделов Хабра
        $this->SaveDataFromMainWebPageToDB($last_scanned_id);

        //2. Публикуем неопубликованные статьи в блоге
        $this->TransferBotArticlesToBlog();

        //Завершение
        $this->SaveStatus(1, '<<<<Bot Ended', $this->bot_id);

    }



}