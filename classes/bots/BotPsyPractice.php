<?php namespace Cwtuning\Bots\Classes\Bots;

require_once __DIR__.'/../phpQuery-onefile.php';



use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\Article;



/*
 *
 * Бот для сканирования сайта по психологии PsyPractice на наличие новых статей
 *
 */
class BotPsyPractice extends Bot
{

    const __BOT_NAME = 'PsyPractice';
    const __SITE_NAME = 'PsyPractice'; //Отобажаемое название сайта
    const __SITE_ADDRESS = 'https://psy-practice.com'; //Главная страница сайта - нужна, если пути на сайте относительные
    protected $title_add = ' - пост с сайта '.self::__SITE_NAME; //добавочка к концу названия поста
    protected $max_blog_posts_per_time = 2; //Переопределим количество статей, за один раз добавляемых с сайта


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
     * Метод для получения адресов статей для парсинга (и создания записей каждой из статьи)
     *
     */
    protected function GetAddressesForParsing($WepPageContent)
    {
        $doc = \phpQuery::newDocument($WepPageContent);
        //echo $doc;
        $doc = pq($doc);

        //Получаем ID новости на сайте, чтобы её однозначно идентифицировать
        $this->ParsePq($doc, '.preview_picture', 'attr', 'href', 'id');

        //Название новости на сайте
        $this->ParsePq($doc, '.newslesttitle-tab', 'text', '', 'title');

        //Ссылка на новость
        $this->ParsePq($doc, '.newslesttitle-tab', 'attr', 'href', 'link');

        //Краткое содержание новости
        $this->ParsePq($doc, '.ptext', 'html', '', 'excerpt');

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
        foreach($doc->find('input[name="backurl"]') as $item)
        {
            $item = pq($item);
            $post_id = $item->attr("value"); //ID поста - /publications/psikhicheskoe-zdorove/devochka-kotoraya-voshla-v-istoriyu-psihologii-pod-im/
        }

        //Ищем количество просмотров - это и будет рейтинг
        foreach($doc->find('div.uk-align-right') as $item)
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
        foreach($doc->find('input[name="backurl"]') as $item)
        {
            $item = pq($item);
            $post_id = $item->attr("value"); //ID поста - /publications/psikhicheskoe-zdorove/devochka-kotoraya-voshla-v-istoriyu-psihologii-pod-im/
        }

        //Ищем тэг, который эквивалентен категории, и добавляем его в массив
        foreach($doc->find('.post-category > a') as $item)
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

                    $this->RecordPosts[$i]->link = self::__SITE_ADDRESS . $this->RecordPosts[$i]->link;
                    $Url = $this->RecordPosts[$i]->link;

                    $res = $this->DownloadLinkToParse($Url); //Скачиваем страницу новости для парсинга
                    $doc = \phpQuery::newDocument($res);
                    $doc = pq($doc);

                    //Находим картинку для новости
                    $img_src = $doc->find('img[width="100%"]:eq(0)')->attr('src');
                    $img_title = $doc->find('img[width="100%"]:eq(0)')->attr('title');
                    $img_add = '<img src="'.self::__SITE_ADDRESS.$img_src.'" width="100%" alt="'.$img_title.'" title="'.$img_title.'"><br>';

                    //Основное содержание новости
                    $this->ParsePq($doc, 'div[id="newsdettext"]', 'html', '', 'content', $i);
                    $this->RecordPosts[$i]->content = $img_add . $this->RecordPosts[$i]->content; //добавляем картинку к основному содержанию новости
                    $this->RecordPosts[$i]->content = $this->ApplyViewToContent(
                        $this->RecordPosts[$i]->content,
                        $this->RecordPosts[$i]->link,
                        self::__SITE_NAME
                    );
                    $this->RecordPosts[$i]->content = str_replace('<div>', '<div class="psy-div">', $this->RecordPosts[$i]->content);


                    //Добавляем также ссылку на источник для excerpt-а
                    $this->RecordPosts[$i]->excerpt = $img_add . $this->RecordPosts[$i]->excerpt;  //добавляем картинку к превью тексту новости
                    /*$this->RecordPosts[$i]->excerpt = $this->ApplyViewToContent(
                        $this->RecordPosts[$i]->excerpt,
                        $this->RecordPosts[$i]->link,
                        self::__SITE_NAME
                    );*/
                    $this->RecordPosts[$i]->excerpt = str_replace('<div>', '<div class="psy-div">', $this->RecordPosts[$i]->excerpt);

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
                    $this->AddArticle($this->RecordPosts[$i]->title,
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
    protected function SaveDataFromMainWebPageToDB()
    {

        //Получаем Юрл для парсинга
        $Url = $this->GetBotScanUrl(self::__BOT_NAME);

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

        //1. Сохраняем в БД найденные на главной странице сайта статьи для последующего размещения в блоге
        $this->SaveDataFromMainWebPageToDB();

        //2. Публикуем неопубликованные статьи в блоге
        $this->TransferBotArticlesToBlog();

        //Завершение
        $this->SaveStatus(1, '<<<<Bot Ended', $this->bot_id);

    }



}