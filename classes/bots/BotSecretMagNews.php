<?php namespace Cwtuning\Bots\Classes\Bots;

require_once __DIR__.'/../phpQuery-onefile.php';



use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\Article;


/*
 *
 * Бот для сканирования сайта Habrahabr на наличие новых статей
 *
 */
class BotSecretMagNews extends Bot
{

    const __BOT_NAME = 'SecretMagNews';
    const __SITE_NAME = 'СекретФирмы'; //Отобажаемое название сайта
    protected $title_add = ' - пост с сайта '.self::__SITE_NAME; //добавочка к концу названия поста
    protected $flow_type = ''; //тип потока




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
     * Метод для получения адреса главной страницы для парсинга в зависимости от потока (Разработка, Администрирование, Дизайн и т.д.)
     *
     */
    protected function GetMainUrl($page_type)
    {
        switch ($page_type)
        {
            case 1:
                $AddPageType = 'news'; //Новости
                break;
            case 2:
                $AddPageType = 'cases'; //Кейсы
                break;
            case 3:
                $AddPageType = 'trends'; //Тренды
                break;
            case 4:
                $AddPageType = 'business'; //Свой бизнес
                break;
            case 5:
                $AddPageType = 'opinions'; //Мненеия
                break;
            default:
                $AddPageType = 'news'; // - По умолчанию - разработка
        }
        $Url = 'https://secretmag.ru/'.$AddPageType;
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
        $this->ParsePq($doc, '.news__title', 'attr', 'href', 'id');

        //Название новости на сайте
        $this->ParsePq($doc, '.news__title', 'text', '', 'title');

        //Ссылка на новость
        $this->ParsePq($doc, '.news__title', 'attr', 'href', 'link');

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
        foreach($doc->find('link[itemprop="url"]') as $item)
        {
            $item = pq($item);
            $post_id = $item->attr("href"); //ID поста - https://secretmag.ru/news/vremennaya-administraciya-fk-otkrytie-spisala-sredstva-so-schetov-menedzherov-banka-23-09-2017.htm
        }
        //Ищем все тэги и добавляем их
        foreach($doc->find('.sheet__tags-item:gt(0)') as $item) //:gt(0) - получаем индексы, больше 0 (пропускаем нулевой, потому что это - не тэг) - https://code.google.com/archive/p/phpquery/wikis/Selectors.wiki
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
                    $fp = fopen(__DIR__.'/temp/temp_file_url.txt', 'wb');
                    fwrite($fp,$res);
                    fclose($fp);
                    $doc = \phpQuery::newDocument($res);
                    $doc = pq($doc);

                    //Основное содержание новости
                    $this->ParsePq($doc, '.g-content', 'html', '', 'content', $i);
                    $this->RecordPosts[$i]->content = str_replace('<a ', '<a target="_blank" ', $this->RecordPosts[$i]->content);
                    $this->RecordPosts[$i]->content = $this->FixLinksAndStyles($this->RecordPosts[$i]->content);
                    $this->RecordPosts[$i]->content = $this->ApplyViewToContent(
                        $this->RecordPosts[$i]->content,
                        $this->RecordPosts[$i]->link,
                        self::__SITE_NAME
                    );
                    $this->RecordPosts[$i]->excerpt = '';

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

                } //if ($IsPublished == 0)

                if ($counter_Posts >= $this->max_blog_posts_per_time) break;

            } //for ($i=0; $i < count($this->RecordPosts)-1; $i++)

        } //if (isset($this->RecordPosts))

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
     * FIXes
     *
     */
    protected function FixLinksAndStyles($content): string
    {
        //preg_match_all('/<div(.*)style="(.*)"/i', $content, $found);
        //FIX Секрет Фирмы - исправляем размеры картинки с сайта Секрет Фирмы
        preg_match_all("/<div(.*)style=\"(.*)\"/i", $content, $found);
        if (isset($found[2][0]))
        {
            $text_to_replace = $found[2][0];
            $content = str_replace($text_to_replace, '', $content);
        }
        //FIX Секрет Фирмы - исправляем ссылки с сайта Секрет Фирмы
        preg_match_all("/<a(.*)href=\"\/(.*)\"/i", $content, $found);
        $host = parse_url($link);
        $host_full = $host['scheme'].'://'.$host['host'];
        if (isset($found[2][0]))
        {
            $text_to_replace = $found[2][0];
            $content = str_replace('"/'.$text_to_replace, '"'.$host_full.'/'.$text_to_replace, $content);
        }
        return $content;
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

        $last_scanned_id = 1;
        //1. Сохраняем найденные статьи в БД для различных разделов Хабра
        $this->SaveDataFromMainWebPageToDB($last_scanned_id);

        //2. Публикуем неопубликованные статьи в блоге
        $this->TransferBotArticlesToBlog();

        //Завершение
        $this->SaveStatus(1, '<<<<Bot Ended', $this->bot_id);

    }



}