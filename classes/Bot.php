<?php namespace Cwtuning\Bots\Classes;

//Модели
use Cwtuning\Bots\Models\Bot as BotModel;
use Cwtuning\Bots\Models\Article as ArticleModel;
use Cwtuning\Bots\Models\ArticleComment as ArticleCommentModel;
use Cwtuning\Bots\Models\ArticleTag as ArticleTag;
use Cwtuning\Bots\Models\Log;
use Cwtuning\Bots\Models\Config;
use Cwtuning\Bots\Models\BlogTitleFilter;


use RainLab\Blog\Models\Category;
use RainLab\Blog\Models\Post as BlogPost;

use Cwtuning\Bots\Classes\Bots\Records;

use Bedard\BlogTags\Models\Tag;


//Различные вспомогательные функции
use Carbon\Carbon;
use Db;




/**
 * Bot class.
 * HTML PARSING: PHPQUERY https://www.youtube.com/watch?v=sP_ItAWOcEg&index=2&list=PLD-piGJ3Dtl0eKhP4gu-B_xypyPvgDLyM
 *
 * @author Ivan Saldikov
 */
class Bot
{
    protected $bot_id; //ID текущего бота из Базы данных
    protected $RecordPosts; //Массив найденных статей
    protected $parser_addr = '';

    protected $max_blog_posts_per_time = 3; //Максимальное количество постов, которое может быть опубликовано за один раз сканирования
    protected $comment_rating_minimum = 1; //минимальный рейтинг коммента, когда он ещё добавляется в БД для публикации в моем блоге
    protected $max_comments_in_db = 40; //Максимальное количество сканируемых с сайта комментов
    protected $max_comments = 10; //Максимальное количество комментов, которое прикрепляется к посту
    protected $max_imgs = 1; //Максимальное количество картинок в новости (чтобы не перегружать новость)


    protected $temp_filename = 'temp.txt'; //Временный файл для загрузки файла с адресами
    protected $tags_table_name = 'bedard_blogtags_post_tag'; //Имя таблицы, в которой хранится связь номеров постов и номеров тэгов (доп. плагин)



    protected $today = ''; //используем эту переменную для поиска текущего времени


    /*
     *
     * При запуске любого бота
     *
     */
    public function __construct()
    {
        $this->today = Carbon::now(); //текущая дата (+3GMT - МСК)
        $this->RecordPosts[] = new Bots\Records\RecordPost();
    }



    /*
     *
     * Метод обновления данных о последнем сканировании и текущем боте
     *
     */
    protected function UpdateGeneralInfo($BotName)
    {

        //Получаем данные об ID бота для Лога
        $Bots_count = BotModel::where('title', $BotName)->count();
        if ($Bots_count > 0)
        {
            $Bots = BotModel::where('title', $BotName)->first();
            $Bots->last_scanned = $this->today;
            $Bots->Update();
            return $Bots->id;
        }
        else
        {
            return -1;
        }

    }



    /*
     *
     * Метод получения URL для сканирования
     *
     */
    protected function GetBotScanUrl($BotName)
    {

        $Bots_count = BotModel::where('title', $BotName)->count();
        if ($Bots_count > 0)
        {
            $scan_url = BotModel::where('title', $BotName)->pluck('parser_addr')->first();
            return $scan_url;
        }
        else
        {
            return '';
        }

    }



    /*
     *
     * Метод получения последнего сканированного ID
     *
     */
    protected function GetBotLastScannedID($BotName)
    {

        $Bots_count = BotModel::where('title', $BotName)->count();
        if ($Bots_count > 0)
        {
            $last_scanned_id = BotModel::where('title', $BotName)->pluck('last_scanned_id')->first();
            return $last_scanned_id;
        }
        else
        {
            return -1;
        }

    }



    /*
     *
     * Метод задания последнего сканированного ID
     *
     */
    protected function SetBotLastScannedID($BotName, $last_scanned_id)
    {

        $Bots_count = BotModel::where('title', $BotName)->count();
        if ($Bots_count > 0)
        {
            $Bots = BotModel::where('title', $BotName)->first();
            $Bots->last_scanned_id = $last_scanned_id;
            $Bots->Update();
            //Для проверки - делаем запрос, чтобы убедиться
            $last_scanned_id = BotModel::where('title', $BotName)->pluck('last_scanned_id')->first();
            return $last_scanned_id;
        }
        else
        {
            return -1;
        }

    }



    /*
     * Логирование - Сохраняем текущий статус бота в таблицу
     */
    public function SaveStatus($action_id,$result,$BotId=-1,$parser_addr='')
    {
        if ($parser_addr == -1) $parser_addr=$this->parser_addr;

        $Config = Config::get()->first();
        $Log = new Log();
        $Log->datetime = $this->today;
        $Log->action_id = $action_id;
        $Log->parser_address = $parser_addr;
        $Log->result = $result;
        $Log->bot_id = $BotId;
        $Log->Save();

        $Config->last_scanned_bot_id = $BotId;
        $Config->update();

        /*        $model = new Main();
                $date_time = date('Y-m-d H:i:s');
                $result = addslashes($result);
                $model->query("UPDATE bots_general_info SET last_scanned_time=\"$date_time\" WHERE id=$this->id");
                $model->query("INSERT INTO bots_log VALUES (NULL, '$this->id', \"$date_time\", '$action_id', '$prog_id', '$result')");*/
        echo $result.'<br>';
    }




    /*
     * Метод загрузки файла с какого-либо адреса
     * protected - значит функция НЕ доступна извне, НО доступна дочерним классам
     */
    protected function DownloadFile($url, $out_fname)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $fp = fopen($out_fname, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }




    /*
     * Метод получения адресов для парсинга из rss
     */
    protected function ReadRSS($url, $out_fname)
    {
        //$this->SaveStatus(2,'Попытка скачать файл: '.$url);
        $this->DownloadFile($url,$out_fname);
        //$this->SaveStatus(3,'Попытка расшифровать RSS-файл');
        $out_xml = file_get_contents($out_fname);
        $dom = new \DOMDocument;
        $dom->loadXML($out_xml);
        if (!$dom) {
            //echo 'Ошибка при разборе документа';
            //$this->SaveStatus(3,'ERROR! Ошибка при разборе документа');
            return [];
        }
        $rss = simplexml_import_dom($dom);
        //$rss->channel[0]->item[0]->title;
        $items = $rss->channel[0]->item;
        //$root = ParseFile($f_name);
        //$items = $root->query("item");
        //$Functions = new funct;
        $i = 0;
        //Для каждой найденной программы проверяем наличие новой версии
        $res_arr = [];
        foreach ($items as $item) {
            //$res_arr[$i] = $item->title;
            $res_arr[$i] = $item->link;
            //$this->SaveStatus(4,'Получение версии для: '.$title.' из '.$link.'...');
            //$this->SaveStatus(4,'Получена ссылка для парсинга: '.$res_arr[$i]); //Сохраняем действия в таблицу
            $i++;
        }
        return $res_arr;
        //$last_Softportal_version = $this->GetVersionFromSoftportalUrl($link);
    }





    /*
     * Метод для получения содержимого страницы по его URL-у для последующего парсинга
     */
    public function DownloadLinkToParse($Url, $saveToFile = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $Url);
        //curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        //curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2" );
        $res = curl_exec($ch);
        if ($saveToFile)
        {
            $fp = fopen(__DIR__.'/bots/temp/temp_file_url_links.txt', 'wb');
            fwrite($fp,$res);
            fclose($fp);
        }
        //var_dump($res);
        //echo htmlspecialchars(print_r($res));
        curl_close($ch);
        return $res;
    }




    /*
     * Метод для проверки, содержит ли заголовок слова, которые публиковать нельзя
     *
     */
    public function FilterBlogTitle($title)
    {
        $res = 0;
        if (trim($title) != '')
        {
            $BlogTitle_Filter = BlogTitleFilter::where('is_on', 1)->get();
            foreach ($BlogTitle_Filter as $BlogTitle_Filter_One)
            {
                $pos1 = stripos($title, $BlogTitle_Filter_One->filtered_word);
                if ($pos1 !== false)
                {
                    $res = 1;
                }
            }
        }
        return $res;
    }




    /*
     *
     * Метод для определения, существует ли файл на удаленном сервере (проверка кода 404)
     *
     */
    public function isUrlExist($url)
    {
        $file = $url;
        $file_headers = @get_headers($file);
        $pos1 = stripos($file_headers[0], '404 Not Found');
        if ($pos1)
        {
            $exists = false;
        }
        else
        {
            $exists = true;
        }
        return $exists;
    }



    /*
     *
     * Метод для применения вида к содержанию статьи
     *
     */
    protected function ApplyViewToContent($text, $link, $site_name)
    {
        $pattern_post_common_arr = file(__DIR__.'/bots/post_patterns/post_common.txt');
        $pattern_post_common_str = implode($pattern_post_common_arr); //превращаем в строку

        $content = str_replace('$CONTENT', $text, $pattern_post_common_str);
        $content = str_replace('$LINK', $link, $content);
        $content = str_replace('$SITE_NAME', $site_name, $content);
        return $content;
    }





    /*
     * Метод добавления статьи в БД в Articles
     * $title, $content, $link, $bot_id, $post_id, $pluses, $minuses, $cat_id
     */
    public function AddArticle($title,
                               $excerpt,
                               $content,
                               $link,
                               $pluses,
                               $minuses,
                               $cat_id,
                               $story_id,
                               $bot_name,
                               $tags)
    {

        $res_id = -1;

        $slug_check = str_slug($title);
        $TheSameArticlesCount = ArticleModel::where('slug', $slug_check)
                ->where('story_id', '=', $story_id)
                ->count(); //для поиска уникальных элементов нужно искать по аттрибутам slug и story_id

        if ($TheSameArticlesCount == 0)
        {

            //$bot_id = $this->UpdateGeneralInfo($bot_name); //Получаем ID бота

            $BotArticle = new ArticleModel();
            $BotArticle->id = NULL;
            $BotArticle->user_id = 1;
            $BotArticle->title = $title;
            $BotArticle->excerpt = $excerpt;
            $BotArticle->content = $content;
            $BotArticle->published_at = $this->today;
            $BotArticle->is_published = 0;
            $BotArticle->post_id = -1; //соответствующий ID поста из блога после публикации - будет добавлен позже
            $BotArticle->cat_id = $cat_id; //ID-категории
            $BotArticle->pluses = $pluses; //плюсы
            $BotArticle->minuses = $minuses; //минусы
            $BotArticle->link = $link; //ссылка на источник
            $BotArticle->story_id = $story_id; //ID новости на сайте -источнике
            $BotArticle->bot_name = $bot_name; //Название бота, который добавил данную новость
            //$BotArticl->bot_id = $bot_id; //кто из ботов добавил статью
            $BotArticle->Save();
            $res_id = $BotArticle->id; //быстрее искать по аттрибуту slug, потому что это ключевое поле
            //FIX!!! $res_id = $BotArticle->id; - doesnt work on PHP 7.1

            //Сохраняем в БД тэги для данной бот-статьи
            foreach ($tags as $tag_name)
            {
                $this->AddTag($res_id, $tag_name);
            }

        }
        return $res_id;

    }



    /*
     *
     * метод для добавления комментария в базу данных для данной статьи
     *
     */
    protected function AddCommentToDb($ArticleID,
                                      $comment_id,
                                      $comment,
                                      $author_name,
                                      $author_img,
                                      $author_url,
                                      $rating,
                                      $datetime,
                                      $link)
    {
        //Проверяем, не добавили ли мы уже этот коммент и если нет, то добавляем
        $IsCommentAlreadyInDB = ArticleCommentModel::where('unique_id', $comment_id)
            ->where('article_id', $ArticleID)
            ->count();
        if ($IsCommentAlreadyInDB == 0)
        {
            $CommentModel_NEW = new ArticleCommentModel();
            $CommentModel_NEW->article_id = $ArticleID;
            $CommentModel_NEW->comment = $comment;
            $CommentModel_NEW->author_display_name = $author_name;
            $CommentModel_NEW->author_profile_image_url = $author_img;
            $CommentModel_NEW->author_profile_url = $author_url;
            $CommentModel_NEW->like_count = $rating;
            $CommentModel_NEW->published_at = $datetime;
            $CommentModel_NEW->unique_id = $comment_id;
            $CommentModel_NEW->link = $link;
            $CommentModel_NEW->Save();
        }

    }



    /*
     *
     * Метод для добавления статьи в блог
     *
     */
    protected function AddArticleToBlog($title,
                                        $excerpt,
                                        $text,
                                        $datetime,
                                        $is_published,
                                        $cat_id,
                                        $user_id)
    {

        $slug = str_slug($title);

        $TheSameArticlesCount = BlogPost::where('slug', $slug)->count();

        //$TheSameArticlesCount = 0;
        if ($TheSameArticlesCount == 0)
        {
            $NewPost = new BlogPost();
            $NewPost -> id = NULL;
            $NewPost -> user_id = $user_id;
            $NewPost -> title = $title;
            $NewPost -> excerpt = $excerpt;
            $NewPost -> content = $text;
            $NewPost -> published = $is_published;
            $NewPost -> published_at = $this->today;
            $NewPost -> created_at = $datetime;
            $NewPost -> updated_at = $this->today;
            $NewPost -> slug = $slug;
            $NewPost -> Save();
            $new_id = $NewPost -> id;

            //Adding connection (ralation) between added post and the category
            $pivot_table_post_cat = $NewPost->belongsToMany['categories']['table']; //Table for adding ralations between posts and categories
            Db::table($pivot_table_post_cat)->insert(
                ['post_id' => $new_id, 'category_id' => $cat_id]
            );
            // $res_msg = '/show_news/'.$NewPost -> slug;
            $res_msg = $new_id;

        }
        else
        {
            $res_msg = 0;
        }
        return $res_msg;

    }




    /*
     *
     * Функция для публикации всех неопубликованных статей Ботов из БД в блог
     *
     */
    public function TransferBotArticlesToBlog()
    {
        $res = -1;
        $addArtToBlog = -1;

        $max_blog_posts_per_time = $this->max_blog_posts_per_time; //(по умолчанию - 2)

        $ArticleItems = ArticleModel::where('is_published', 0)
            ->orderBy('id', 'asc')
            ->take($max_blog_posts_per_time)
            ->get();

        foreach ($ArticleItems as $ArticleItem)
        {

            if (isset($ArticleItem->title))
            {

                if (
                    ($this->FilterBlogTitle($ArticleItem->title) == 0)
                    AND
                    ($this->FilterBlogTitle($ArticleItem->content) == 0)
                    AND
                    ($this->FilterBlogTitle($ArticleItem->excerpt) == 0)
                )
                {
                    if (isset($ArticleItem->cat_id))
                    {

                        $content = $ArticleItem->content;
                        $excerpt = $ArticleItem->excerpt;

                        //Получаем комментарии для данной статьи и добавляем их к содержанию
                        $comments_view = $this->GetCommentsView($ArticleItem->id);
                        $content .= $comments_view;
                        if ($excerpt != '')
                        {
                            $excerpt .= $comments_view;
                        }

                        //Добавляем статью в блог: -1 - ошибка, 0 - статья с таким названием уже существует, >0 - Id статьи
                        $addArtToBlog = $this->AddArticleToBlog($ArticleItem->title,
                            $excerpt,
                            $content,
                            $ArticleItem->published_at,
                            1,
                            $ArticleItem->cat_id,
                            2);

                        //Если у нас есть новый пост, то соединяем добавленные ранее к Статье тэги с данным постом
                        if ($addArtToBlog > 0)
                        {
                            $this->TransferTagsFromArticleToPost($ArticleItem->id, $addArtToBlog);
                        }

                        if ($addArtToBlog > -1)
                        {
                            $res = 1;
                        }

                    }//if (isset($ArticleItem->cat_id))

                } //if ($this->FilterBlogTitle($ArticleItem->title) > 0)
                else
                {
                    //Если есть фильтр-слово, то обозначаем, что статья опубликована, но ID её будет равен 0
                    $res = 0;
                    $addArtToBlog = 0;
                    echo '<br><font color="orange">Пост '.$ArticleItem->title.' НЕ опубликован в блоге из-за наличия стоп-слов в заголовке!</font><br>';
                }

            } //if (isset($ArticleItem->title))

            if ($res > -1)
            {
                $ArticleItem->is_published = 1;
                $ArticleItem->post_id = $addArtToBlog;
                    echo '<br><font color="#006400">Пост '.$ArticleItem->title.' успешно опубликован в блоге!</font><br>';
                $ArticleItem->Update();

                //Удаляем лишние комменты из базы данных
                //$this->DeletePublishedComments($ArticleItem->id);
            }

        } //foreach (foreach ($ArticleItems as $ArticleItem))

        return $res;

    }



    /*
     *
     * Метод для формирование содержания комментариев по шаблону вида из той или иной статьи и выдача в виде HTML-текста
     *
     */
    protected function GetCommentsView($ArticleID)
    {

        $total_comments = '';

        //Подгружаем вид
        $pattern_post_common_comments_arr = file(__DIR__.'/bots/post_patterns/post_common_comments.txt');
        $pattern_post_common_comments = implode($pattern_post_common_comments_arr);
        $comment_rating_min = $this->comment_rating_minimum;

        //Добавляем в содержание новости комментарии
        $CommentItems = ArticleCommentModel::where('article_id', $ArticleID)
            ->where('like_count', '>', $comment_rating_min)
            ->OrderBy('like_count', 'desc')
            ->take($this->max_comments)//берем только первые 10 (по умолчанию) комментов limit
            ->get();

        foreach ($CommentItems as $CommentItem)
        {
            $comment_in = str_replace("<img", "<img class=\"comment_img\"", $CommentItem->comment);

            $comments = str_replace('$USER_NAME', $CommentItem->author_display_name, $pattern_post_common_comments);
            $comments = str_replace('$USER_DATE', $CommentItem->published_at, $comments);
            $comments = str_replace('$COMMENT', $comment_in, $comments);
            $comments = str_replace('$LIKES', $CommentItem->like_count, $comments);
            $comments = str_replace('$LINK', $CommentItem->link, $comments);
            $total_comments .= $comments;

        }

        return $total_comments;

    }



    /*
     *
     * Метод для превращения тэгов статьи в тэги блога
     *
     */
    protected function TransferTagsFromArticleToPost($ArticleID, $PostID)
    {
        if ($PostID > 0)
        {
            $ArticleTags = ArticleTag::where('article_id', $ArticleID)->get();
            if (isset($ArticleTags))
            {
                foreach ($ArticleTags as $tag)
                {
                    $is_tag_in_the_post = Db::table($this->tags_table_name)
                        ->where('tag_id', $tag->tag_id)
                        ->where('post_id', $PostID)
                        ->count();
                    if (!$is_tag_in_the_post)
                    {
                        Db::insert('insert into '.$this->tags_table_name.' (tag_id, post_id) values (?, ?)', [$tag->tag_id, $PostID]);
                    }
                }
            }
        }

    }



    /*
     *
     * Метод для добавления тэга для определенного поста из БЛОГа, который уже добавлен в БД
     * @Возвращает TagID из БД
     *
     */
    protected function AddTagToPost($PostID, $tag_name)
    {

        $tag_name_small = mb_strtolower($tag_name);
        $tag_name_slug = str_slug($tag_name_small);
        $tag_id = Tag::where('slug', $tag_name_slug)->pluck('id')->first();
        //Если такого тэга нет, то создаем
        if (!isset($tag_id))
        {
            $TagNewModel = new Tag();
            $TagNewModel->name = $tag_name_small;
            $TagNewModel->slug = $tag_name_slug;
            $TagNewModel->Save();
            $tag_id = $TagNewModel->id;
        }

        if ($PostID > 0)
        {
            $is_tag_in_the_post = Db::table($this->tags_table_name)
                ->where('tag_id', $tag_id)
                ->where('post_id', $PostID)
                ->count();
            if (!$is_tag_in_the_post)
            {
                Db::insert('insert into '.$this->tags_table_name.' (tag_id, post_id) values (?, ?)', [$tag_id, $PostID]);
            }
        }

        return $tag_id;

    }



    /*
     *
     * Метод для удаления лишних комментариев из БД для той или иной бот-статьи
     *
     */
    protected function DeletePublishedComments($ArticleID)
    {

        ArticleCommentModel::where('article_id', $ArticleID)
            ->delete();
        echo '<font color="#d2691e">----Для поста с ID "'.$ArticleID.'" успешно очищен кэш комментариев из базы данных</font>.<br>';

    }


    /*
     *
     * Находим в Pq-объекте $obj необходимый нам элемент с именем $name и типом $type текст, html и тд.
     *
     */
    protected function ParsePq($obj, $name, $type, $attrName, $record_name, $in_j = -1)
    {
        $i = 0;
        //echo '<br>$name = > '.$name;
        //Получаем информацию о новости на сайте и заносим в массив RecordPost
        foreach($obj->find($name) as $item)
        {
            //if ($record_name == 'content') echo 'OK3';
            $item = pq($item);
            switch ($type)
            {
                case 'text':
                    $out = $item->text();
                    break;
                case 'html':
                    $out = $item->html();
                    break;
                case 'attr':
                    $out = $item->attr($attrName);
                    break;
                default:
                    $out = $item->text();
            }
            //Если же нам нужно заполнить конкретный номер, то просто указываем его
            if ($in_j != -1)
            {
                $i = $in_j;
            }

            if (!isset($this->RecordPosts[$i]))
            {
                $this->RecordPosts[] = new Records\RecordPost();
            }
            $this->RecordPosts[$i]->$record_name = $out;
            //echo '$this->RecordPosts[$i]->$record_name='.$this->RecordPosts[$i]->$record_name.'<br>';
            $i++;
            //echo ' => '.$out.'<br>';
        }

    }



    /*
     *
     * Метод для получения ID категории, к которой принадлежит соответствующий бот
     *
     */
    protected function GetBotCatID($BotName)
    {
        $BotID = BotModel::where('title', $BotName)->pluck('id')->first(); //Бот - получаем его ID
        $BotModel = new BotModel();
        $pivot_table = $BotModel->belongsToMany['categories']['table']; //Table for adding ralations between bots and categories
        $cat_id = Db::table($pivot_table)
            ->where('bot_id', $BotID)
            ->pluck('category_id')
            ->first();
        return $cat_id;
    }




    /*
     *
     * Получаем ID категории, в которую мы хотим поместить новость - поиск по названию категории
     *
     */
    protected function GetCatIDByName($CatName): int
    {
        $res = Category::where('slug', $CatName)->pluck('id')->first();
        return $res;
    }




    /*
     *
     * Получаем Отображаемое имя категории - поиск по ID категории
     *
     */
    protected function GetCatNameById($cat_id)
    {
        return Category::where('id', $cat_id)->pluck('slug')->first();
    }





    /*
     *
     * Получаем текущий час
     *
     */
    public function GetHourNow()
    {
        return $this->today->hour; //Получаем текущий час (для запуска ботов по расписанию)
    }




    /*
     *
     * Тестирование решения вопроса с кодировкой UTF8mb4 (поддержка кодов символов UTF8)
     *
     */
    public function Test()
    {
        //Скачиваем страницу новости для парсинга - здесь есть смайклик - символ UTF-8, который не добавлялся в базу раньше
        $res = $this->DownloadLinkToParse('https://pikabu.ru/story/odnako_5309827');
        $doc = \phpQuery::newDocument($res);
        $doc = pq($doc);


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


            echo $content_html;

            $this->AddArticle('Test',
                '',
                $content_html,
                'http://yandex.ru',
                1,
                2,
                13,
                11111111,
                'BotPikabu',
                []
            );


        }

    }


    /*
     *
     * Получаем список всех включенных ботов для запуска
     *
     */
    public function BeforeAll()
    {

        //Проверяем количество ботов, которое осталось запустить (тех, у которых ID>последнего)
        $Config = Config::Get()->first();
        //$last_bot_scanned_id = $Config->last_scanned_bot_id;
        //$Bots_Count = BotModel::where('is_on', 1)->where('id', '>', $last_bot_scanned_id)->count();
        $Bots_Count = BotModel::where('is_on', 1)->count();
        if ($Bots_Count == 0)
        {
            $last_bot_scanned_id = 0;
            $Config->last_scanned_bot_id = $last_bot_scanned_id;
            $Config->Update();
        }

        //return BotModel::where('is_on', 1)->where('id', '>', $last_bot_scanned_id)->get();
        return BotModel::where('is_on', 1)->get();

    }



    /*
     *
     * Добавляем тэг для только что добавленной статьи
     *
     */
    protected function AddTag($article_id, $tag_name)
    {
        $tag_name_small = mb_strtolower($tag_name);
        $tag_name_slug = str_slug($tag_name_small);
        $tag_id = Tag::where('slug', $tag_name_slug)->pluck('id')->first();
        //Если такого тэга нет, то создаем
        if (!isset($tag_id))
        {
            $TagNewModel = new Tag();
            $TagNewModel->name = $tag_name_small;
            $TagNewModel->slug = $tag_name_slug;
            $TagNewModel->Save();
            $tag_id = $TagNewModel->id;
        }
        $ArticleTagNewModel = new ArticleTag();
        $ArticleTagNewModel->tag_id = $tag_id;
        $ArticleTagNewModel->article_id = $article_id;
        $ArticleTagNewModel->Save();
        return $tag_id;
    }




}