<?php namespace Cwtuning\Bots\Classes\Bots;

require_once __DIR__.'/../phpQuery-onefile.php';


use Cwtuning\Bots\Classes\Bot;

/*
 *
 * Бот для сканирования сайта Softportal на наличие новых версий программ
 *
 */
class BotSoftportal extends Bot
{

    const __BOT_NAME = 'SoftportalBot';
    const __SITE_NAME = 'Softportal'; //Отобажаемое название сайта
    protected $title_add = ' - пост с сайта '.self::__SITE_NAME; //добавочка к концу названия поста
    protected $last_scanned_id; //Последний ID сканирования


    /*
     * Метод для обработки страницы с нужными данными с сайта Softportal и занесения данных в данные класса (title, content, source)
     */
    public function GetDataFromUrl($Url)
    {

        $item = new Records\RecordPost(); //сюда сохраняем накопленную информацию

        $res = $this->DownloadLinkToParse($Url); //Скачиваем страницу для парсинга

        $title = '';
        $prog_caption = '';
        $story_id = -1;

        $doc = \phpQuery::newDocument($res);
        $doc = pq($doc);
        //$this->news_info = []; //Опустошаем массив
        //Story ID
        foreach ($doc->find('link[rel="canonical"]') as $content)
        {
            $content = pq($content);
            $link = $content->attr('href');
            //Полученная строка будет вида "http://www.softportal.com/software-31385-soft4boost-any-audio-grabber.html" - нам нужен номер
            $pieces = explode("-", $link);
            if (isset($pieces[1]))
            {
                $story_id = $pieces[1];
            }
        }
        //Рейтинг средний
        $rating_avg = 0;
        foreach ($doc->find('span[itemprop="ratingValue"]') as $content)
        {
            $content = pq($content);
            $rating_avg = $content->text();
        }
        //Кол-во проголосовавших
        $rating_count = 0;
        foreach ($doc->find('span[itemprop="ratingCount"]') as $content)
        {
            $content = pq($content);
            $rating_count = $content->text();
        }
        $rating_total = $rating_avg * $rating_count;
        //Название программы
        foreach ($doc->find('#headLine') as $name_result)
        {
            $name_result = pq($name_result);
            //$link = $name_result->attr('href');
            //Полученная строка содержит строку "Программы : Adguard / скачать Adguard 6.1.298"
            $prog_caption = $name_result->text();
            //Нам нужно исключить лишнее и взять версию
            $prog_caption = str_replace("Программы : ", "", $prog_caption);
            $prog_caption = str_replace(" / скачать ", "/", $prog_caption);
            $pieces = explode("/", $prog_caption);
            $prog_caption = $pieces[1];
            $title = 'Вышла новая версия программы ' . $prog_caption;
            //echo $this->news_info['title'];
            //$prog_ver = str_replace($pieces[0], "", $pieces[1]);
        }
        $content = $title . '<br><br>';
        //Картинки
        foreach ($doc->find('.popup-gallery') as $name_result)
        {
            $name_result = pq($name_result); //превращаем в объект PHPQuery, чтобы с ним потом работать (искать в нем различные элементы)
            $imgs_arr_big = [];
            $imgs_arr_small = [];
            $i = 0;
            foreach ($name_result->find('img') as $imgs)
            {
                $imgs = pq($imgs);
                $imgs_arr_small[$i] = $imgs->attr('src'); //нам нужен только аттрибут src
                //echo $imgs_arr_small[$i].'<br>';
                $i++;
            }
            $i = 0;
            foreach ($name_result->find('a') as $imgs_big)
            {
                $imgs = pq($imgs_big);
                $imgs_arr_big[$i] = $imgs->attr('href'); //нам нужен только аттрибут href
                //echo $imgs_arr_big[$i].'<br>';
                $i++;
            }
            $temp_imgs_show = '';
            count($imgs_arr_small) > $this->max_imgs ? $max_imgs = $this->max_imgs : $max_imgs = count($imgs_arr_small);
            for ($i = 0; $i < $max_imgs; $i++)
            {
                $temp_imgs_show .= '<a target="_blank" href="http://www.softportal.com/' . $imgs_arr_big[$i] . '"><img title="Скриншот программы '.$prog_caption.'" alt="Скриншот программы '.$prog_caption.'" border="0" width="400px;" src="http://www.softportal.com/' . $imgs_arr_big[$i] . '"></a><br><br>';
            }
            //$link = $name_result->attr('href');
            //Полученная строка содержит строку "Программы : Adguard / скачать Adguard 6.1.298"
            //$prog_caption = $name_result->text();
            //Нам нужно исключить лишнее и взять версию
            $content .= $temp_imgs_show;

            //echo $this->news_info['title'];
            //$prog_ver = str_replace($pieces[0], "", $pieces[1]);
        }
        //Описание
        foreach ($doc->find('#desc') as $name_result)
        {
            $name_result = pq($name_result);
            $content .= $name_result->html();
        }
        //Ссылка
        $download_link = $doc->find('.menu2AN')->attr('href');
        $content .= '<p><a target="_blank" href="' . $download_link . '">Перейти на сайт для скачивания программы</a></p>';

        //Тэги
        $item->tags = [];
        //добавляем тэги со страницы (разделы, в которых представлена программа, разработчика, название)
        foreach ($doc->find('span[itemprop="name"]') as $phpQtags)
        {
            $phpQtags = pq($phpQtags);
            $tag_name = $phpQtags->text();
            //Разбиваем запятые
            $pieces = explode(", ", $tag_name);
            foreach ($pieces as $piece)
            {
                $item->tags[] = $piece;
            }
        }
        $item->tags[] = mb_strtolower(self::__SITE_NAME); //добавляем тэг Софтпортала


        //Сохраняем в массив и возвращаем
        $item->title = $title;
        $item->content = $content;
        $item->link = $Url;
        $item->pluses = $rating_total; //рейтинг: средний рейтинг * кол-во проголосовавших
        $item->minuses = 0; //минусы
        $item->cat_id = $this->GetBotCatID(self::__BOT_NAME); //ID-категории
        $item->story_id = $story_id; //ID ссылки, чтобы быстро найти соответствующую новость (защита от повторной публикации)
        $item->bot_name = self::__BOT_NAME;

        return $item;
    }



    /*
     * Метод для поочередного парсинга найденных адресов и добавления их в БД статей ботов
     */
    protected function ParseAddresses($addresses)
    {

        $counter = 0;

        for($i = $this->last_scanned_id; $i < count($addresses); $i++)
        {
            if (!isset($this->RecordPosts[$i]))
            {
                $this->RecordPosts[$i] = new Records\RecordPost();
            }
            $this->RecordPosts[$i] = $this->GetDataFromUrl($addresses[$i]); //Получаем данные со страницы и заносим в описание класса
            if ($this->RecordPosts[$i]->title != '')
            {

                //Добавляем статью в базу данных
                $this->AddArticle($this->RecordPosts[$i]->title,
                    '',
                    $this->RecordPosts[$i]->content,
                    $this->RecordPosts[$i]->link,
                    $this->RecordPosts[$i]->pluses,
                    $this->RecordPosts[$i]->minuses,
                    $this->RecordPosts[$i]->cat_id,
                    $this->RecordPosts[$i]->story_id,
                    self::__BOT_NAME,
                    $this->RecordPosts[$i]->tags
                );

                $counter++;

            }
            if ($counter >= $this->max_blog_posts_per_time)
            {
                $this->last_scanned_id = $i+1;
                break;
            }
        }
        if ($counter == 0) $this->last_scanned_id = 0; //Сбрасываем последний ID, если уже дошли до последнего адреса и добавлено ничего не было

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
        $this->last_scanned_id = $this->GetBotLastScannedID(self::__BOT_NAME);
        $this->SaveStatus(1, 'Bot Run>>>>', $this->bot_id);

        //Инициализация внутренних переменных
        //$Bot = new BotSoftportal();
        $temp_filename = __DIR__."/temp/rss-softportal-update.xml";
        $addr_init = 'http://www.softportal.com/rss/rss-soft-update-win.xml'; //Какую страницу анализируем

        //1. Получаем адреса для парсинга
        $addresses = $this->ReadRSS($addr_init, $temp_filename); //Получаем массив адресов для парсинга

        //2. Обрабатываем адреса
        $this->ParseAddresses($addresses); //Теперь парсим данные с полученных адресов

        //3. Публикуем неопубликованные новости в блоге
        $this->TransferBotArticlesToBlog();

        //Завершение
        $this->SetBotLastScannedID(self::__BOT_NAME, $this->last_scanned_id);
        $this->SaveStatus(1, '<<<<Bot Ended', $this->bot_id);

    }




}

