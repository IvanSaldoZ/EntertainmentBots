<?php namespace Cwtuning\Bots\Classes\Bots;

//Facebook API
require_once __DIR__ . '/../../../../../vendor/facebook/autoload.php';


use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\Bot as BotModel;

use Cwtuning\Instagrammediaparser\Models\InstagramMedia;
use Cwtuning\Social\Components\BotPostExportVK;

use Cwtuning\Social\Classes\Model_Vk;

use Cwtuning\Bots\Classes\Google;





class BotInstagramLena extends Bot
{

    const __BOT_NAME='InstagramLenaBot';
    const __INSTG_WEBLINK = 'https://www.instagram.com';



    /*
     *
     * Превращем из строки сжатого изображения Instagram https://instagram.fhrk1-1.fna.fbcdn.net/t51.2885-15/sh0.08/e35/p640x640/14736264_521663234704975_7279842573126991872_n.jpg
     * в строку (лучшее качество)                        https://instagram.fhrk1-1.fna.fbcdn.net/t51.2885-15/sh0.08/e35/14736264_521663234704975_7279842573126991872_n.jpg
     *                              NOT WORK ANYMORE: https://instagram.fhen1-1.fna.fbcdn.net/t51.2885-15/e35/14677387_1780922892162461_3385381252356898816_n.jpg
     *
     */
    protected function GetFullPhotoOfInstagram($media_image)
    {
        $media_image = str_replace("p640x640/", "", $media_image);
        $media_image = str_replace("s640x640/", "", $media_image);
        $media_image = str_replace("c180.0.720.720/", "", $media_image);
        $media_image = str_replace("c0.105.1080.1080/", "", $media_image);
        $media_image = str_replace("e35/", "", $media_image);
        $media_image = str_replace("sh0.08/", "", $media_image);

        return $media_image;
    }


    /*
     * Метод для получения данных с сайта Instagram для конкретного пользователя и занесения этих данных в базу данных сайта
     * https://habrahabr.ru/post/302150/
     */
    public function GetDataForUser()
    {
        $user_name = 'mom_lena';
        $BotModel = BotModel::where('title', 'InstagramLenaBot')->first();
        $max_id = $BotModel->last_scanned_id;
        //echo '$max_id>>>'.$max_id.'<br>';
        $added_str = '';
        //$max_id = '1498756354210614318_1544929802';
        if ($max_id != '')
        {
            $added_str = '?max_id=' . $max_id;
        }
        $query_string = self::__INSTG_WEBLINK.'/' . $user_name . '?__a=1';
        //' . $added_str;
        //echo '$query_string>>'.$query_string;
        //$query_string = 'https://www.instagram.com/'.$user_name.'/media/';
        $response = file_get_contents(rtrim($query_string));
        $mediaArray = json_decode($response, true);
        //var_dump($mediaArray);
        $i = 0;
        //dd();
        $medArr = $mediaArray['user']['media']['nodes'];
        //var_dump($medArr);
        //dd();

        $last_scanned_id = '';
        foreach ($medArr as $item)
        {
            $last_scanned_id = $item['id'];
            $NewInstagramMedia = new InstagramMedia();
            if ($NewInstagramMedia->where('media_id', $last_scanned_id)->count() == 0)
            {
                $NewInstagramMedia->id = NULL;
                $NewInstagramMedia->user_id = $item['owner']['id']; //ID пользователя, который добавил media
                $NewInstagramMedia->media_id = $item['id']; //Media ID
                $NewInstagramMedia->media_code = $item['code']; //BTj0qv4FjWY
                $text = json_encode($item['caption']); //нужно сохранить в виде json-строки, иначе потреяем форматирование
                $NewInstagramMedia->media_caption = $text; //Текст поста
                $lnk = self::__INSTG_WEBLINK.'/p/'.$item['code']; //Ссылка на пост в Instagram
                $NewInstagramMedia->media_link = $lnk;
                $NewInstagramMedia->media_created_time = $item['date']; //Timestamp - когда добавлена запись
                $NewInstagramMedia->media_image = $item['display_src']; //Картинка
                $NewInstagramMedia->vk_published_date = NULL; //Дата публикации в ВК
                $NewInstagramMedia->fb_published_date = NULL; //Дата публикации в ФБ
                $NewInstagramMedia->odnokl_published_date = NULL; //Дата публикации в Одноклассниках
                $NewInstagramMedia->youtube_published_date = NULL; //Дата публикации на Ютубе
                //Если заданы видео
                if (isset($item['videos']))
                {
                    $NewInstagramMedia->media_video = $item['videos']['standard_resolution']['url']; //Ссылка на видео
                } else
                {
                    $NewInstagramMedia->media_video = NULL; //Ссылка на видео
                }
                $this->SaveStatus(99,
                    'SUCCESS! Instagram media ' . $lnk . ' is successfully added to the database for later publishing in the Social Networks',
                    $this->bot_id,
                    $lnk
                );
                $NewInstagramMedia->Save();
                //$text = json_decode($text, true);
                //echo $text['text'].'<br><br><br><br><hr>';
                //            $i++;
                //            if ($i == 2) exit;
            } else
            {
                //Если уже есть такое, то делаем $last_scanned_id, чтобы начать сначала поиск
                $last_scanned_id = '';
            }

        }
        //Сохраняем последний ID для следующего сканирования
        //echo '$last_scanned_id>>>'.$last_scanned_id.'<br>';
        $BotModel->last_scanned_id = $last_scanned_id;
        $BotModel->Update();
        return $last_scanned_id;

    }


    /*
     * Метод для публикации фото и видео из базы данных сайта на сайт BK по очереди для тех записей, которые ещё не опубликованы (NULL)
     */
    public function PublishToVK()
    {
        $res = 0;
        //Выбираем те записи, которые ещё не опубликованы
        $InstagramMedia_Count = InstagramMedia::whereNull('vk_published_date')->OrderBy('media_created_time', 'asc')->count();
        if ($InstagramMedia_Count > 0)
        {
            $InstagramMedia = InstagramMedia::whereNull('vk_published_date')->OrderBy('media_created_time', 'asc')->first();
            //$InstagramMedia = InstagramMedia::where('media_id', '972598765041684170_1544929802')->first();
            $media_id = $InstagramMedia->media_id;
            $text = $InstagramMedia->media_caption;
            $media_link = $InstagramMedia->media_link;
            $media_video = $InstagramMedia->media_video;
            $media_image = $this->GetFullPhotoOfInstagram($InstagramMedia->media_image);
            $text = json_decode($text, true);
            //$message = $text['text'];
            $message = $text;
           // echo '$media_image = '.$media_image;
           // echo '<br>$message = '.$message;

            //echo $text['text'].'<br><br><br><br><hr>';
            //$link = $InstagramMedia->media_link;

            if ($this->isUrlExist($media_image))
            {
                $guid = 'id_' . $media_id;
                $link = '';
                $video = '';
                $picture = '';

                $new_VK = new BotPostExportVK();
                $new_VK->SetGroupID($this->group_id);
                //var_dump($media_video);
                if ($media_video != NULL)
                {
                    $video = $this->UploadVideoFromInstagramToVK($media_id);
                }
                else
                {
                    $picture = $this->UploadPhotosFromTheTextToVK($media_id);
                }

                $res = $new_VK->PublishPostToVk($message, $guid, $link, $video, $picture, $this->group_id);

                if ($res == -214)
                {
                    $this->SaveStatus(99,
                        'ERROR! Instagram media ' . $InstagramMedia->media_link . ' not uploaded to VK. 50 posts exceeded (Error -214)',
                        $this->bot_id,
                        $media_link
                    );
                }
                else
                {
                    $InstagramMedia->vk_published_date = $this->today;
                    $InstagramMedia->Update();
                    $this->SaveStatus(99,
                        'SUCCESS! Instagram media ' . $InstagramMedia->media_link . ' is successfully published to VK: https://vk.com/mom_lena?w=wall-' . $this->group_id . '_' . $res,
                        $this->bot_id,
                        $media_link
                    );
                }
            }
            else
            {
                $InstagramMedia->delete(); //удаляем, если не найдено
                $this->SaveStatus(99,
                    'ERROR! Instagram media ' . $InstagramMedia->media_link . ' is not found. It is deleted from the database.',
                    $this->bot_id,
                    $media_link
                );
                echo 'ERROR: Url not found:'.$media_link.'. Image deleted from database';
                $res = -214;
            }
        }
        return $res;
    }


    /*
     *
     * Добавить видео Вконтакте. Возвращает ссылку на видео (для аттачмента публикации Вконтакте
     * формат выода: video$GROUPID_$VIDEOID. При ошибке - пустая строка)
     *
     */
    public function UploadVideoFromInstagramToVK($media_id)
    {
        $res = '';
        //Выбираем те записи, которые ещё не опубликованы
        $InstagramMedia = InstagramMedia::where('media_id', $media_id)->first();
        if ($InstagramMedia->vk_published_date == NULL)
        {
            $media_video = $InstagramMedia->media_video;
            $link = $InstagramMedia->media_link;
            $description = $InstagramMedia->media_caption;
            $description = json_decode($description, true);
            //$description = $description['text'];
            $description = $description;

            //Убираем смайлики из текста - в описании к видео они НЕ преобразуются
            //http://uvsoftium.ru/php/regexp.php - регулярные выражения онлайн
            $pattern = '/(:[A-z]*:)/';
            $replacement = '';
            $description = preg_replace($pattern, $replacement, $description);

            //Максимальная подпись видео (максимум символов в ВК: 1000)
            if (strlen($description) > 900)
            {
                $description = substr($description, 0, 900);
                $description = rtrim($description, "!,.-");
                $description = substr($description, 0, strrpos($description, ' '));
                $description = $description . '...';
            }

            //Делаем Caption для видео-ролика - обрезаем лишнее и делаем троеточие (максимум символов в ВК: 128)
            $caption = $description;
            if (strlen($caption) > 125)
            {
                $caption = substr($caption, 0, 125);
                $caption = rtrim($caption, "!,.-");
                $caption = substr($caption, 0, strrpos($caption, ' '));
                $caption = $caption . '...';
            }

            $description = $description . '
// Ссылка на Инстаграм: ' . $link;

            $BotPostExpVK = new BotPostExportVK();
            $vk = new Model_Vk($BotPostExpVK->access_token);

            //Загружаем изображение и получаем его ID (елси ошибка загрузки, то false)
            $upload_video = $vk->uploadVideo($media_video,
                $this->group_id,
                $caption,
                $description);

            if ($upload_video > 0)
            {
                $res = 'video-' . $this->group_id . '_' . $upload_video; //формат: video$GROUPID_$VIDEOID
            }
        }
        return $res;
    }


    /*
     *
     * Добавить картинку в Альбом Вконтакте. Возвращает ссылку на картинку (для аттачмента публикации Вконтакте
     * формат выода: photo-$GROUPID_$PHOTOID. При ошибке - пустая строка)
     *
     */
    public function UploadPhotosFromTheTextToVK($media_id)
    {
        //Ищем наличие ссылку на картинку в содержании
        //$content = '<img src="http://someurl/a79a2277b00a43efc4f5d3fce8b0fba6.gif" id="MyID">';
        $res = '';
        //Выбираем те записи, которые ещё не опубликованы
        $InstagramMedia = InstagramMedia::where('media_id', $media_id)->first();
        if ($InstagramMedia->vk_published_date == NULL)
        {
            $media_image = $this->GetFullPhotoOfInstagram($InstagramMedia->media_image);
            $link = $InstagramMedia->media_link;
            $text = $InstagramMedia->media_caption;
            $text = json_decode($text, true);
            //$title = $text['text'];
            $title = $text;
            //Максимальная подпись фотографии, походу 2000 символов, поэтому обрезаем лишнее, чтобы оставить место для ссылки на инстаграм
            if (strlen($title) > 1900)
            {
                $title = substr($title, 0, 1900);
                $title = rtrim($title, "!,.-");
                $title = substr($title, 0, strrpos($title, ' '));
                $title = $title . '...';
            }
            $title = $title . '
// Ссылка на Инстаграм: ' . $link;

            $BotPostExpVK = new BotPostExportVK();
            $vk = new Model_Vk($BotPostExpVK->access_token);


            //Загружаем изображение и получаем его ID (елси ошибка загрузки, то false)
            $upload_img = $vk->uploadImage($media_image,
                $this->group_id,
                $this->album_id,
                $title);

            if ($upload_img)
            {
                $res = 'photo-' . $this->group_id . '_' . $upload_img; //формат: photo-$GROUPID_$PHOTOID
            }
        }
        return $res;
    }

    protected $group_id = '146146959';
    protected $album_id = '244095536';


    /*
     *
     * Получение токена (т.н. маркера доступа) из краткосрочного в тот, который дествует 60 суток
     * http://stackoverflow.com/questions/18027053/php-facebook-api-page-access-tokens
     * https://developers.facebook.com/docs/facebook-login/access-tokens/expiration-and-extension
     * https://developers.facebook.com/docs/facebook-login/access-tokens/#pagetokens
     * https://developers.facebook.com/docs/pages/access-tokens
     *
     */
    public function FacebookGetTokenFrom1HourTo60Days($token)
    {
        //$short_lived_token = '';
        $short_lived_token = $token;


        //Facebook-авторизация
        $fb = new \Facebook\Facebook([
            'app_id' => $this->facebook_app_id,
            'app_secret' => $this->facebook_app_secret,
            'default_graph_version' => 'v2.9',
        ]);

        //Facebook-получение access token-а для редактирования страницы

        $string = '/oauth/access_token?grant_type=fb_exchange_token&client_id=' . $this->facebook_app_id .
            '&client_secret=' . $this->facebook_app_secret .
            '&fb_exchange_token=' . $short_lived_token;
        //echo 'https://graph.facebook.com'.$string;
        try
        {
            // Returns a `Facebook\FacebookResponse` object
            $response = $fb->get($string);
        } catch (\Facebook\Exceptions\FacebookResponseException $e)
        {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e)
        {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        $decoded_Body = $response->getDecodedBody();
        $sixty_days_token = $decoded_Body['access_token'];

        return $sixty_days_token;

    }


    /*
     *
     * Получение бесконечного токена для страницы из того, который действует 60 суток (см. метод FacebookGetTokenFrom1HourTo60Days)
     * http://stackoverflow.com/questions/18027053/php-facebook-api-page-access-tokens
     * https://developers.facebook.com/docs/facebook-login/access-tokens/expiration-and-extension
     * https://developers.facebook.com/docs/facebook-login/access-tokens/#pagetokens
     * https://developers.facebook.com/docs/pages/access-tokens
     *
     */
    public function FacebookGetTokenFrom60DaysToUnlimited($token, $group_id)
    {
        //$sixty_days_token = '';
        $sixty_days_token = $token;
        //$group_id = $this->facebook_group_id;

        $unlimited_token = '';

        //Facebook-авторизация
        $fb = new \Facebook\Facebook([
            'app_id' => $this->facebook_app_id,
            'app_secret' => $this->facebook_app_secret,
            'default_graph_version' => 'v2.9',
        ]);

        //Facebook-получение бесконечного access token-а для редактирования страницы
        try
        {
            // Returns a `Facebook\FacebookResponse` object
            $response = $fb->get('/' . $group_id . '?fields=access_token', $sixty_days_token);
        } catch (\Facebook\Exceptions\FacebookResponseException $e)
        {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e)
        {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        $decoded_Body = $response->getDecodedBody();
        $unlimited_token = $decoded_Body['access_token'];

        //var_dump($response);

        return $unlimited_token;

    }


    /*
     * Метод для публикации фото и видео из базы данных сайта на Фейсбук по очереди для тех записей, которые ещё не опубликованы (NULL)
     */
    public function PublishToFaceBook()
    {
        $res = 0;

        //Выбираем те записи, которые ещё не опубликованы
        $InstagramMedia_NotPublished_Count = InstagramMedia::whereNull('fb_published_date')->
        OrderBy('media_created_time', 'asc')
            ->count();

        if ($InstagramMedia_NotPublished_Count > 0)
        {
            $InstagramMedia = InstagramMedia::whereNull('fb_published_date')->
            OrderBy('media_created_time', 'asc')
                ->first();
            //$InstagramMedia = InstagramMedia::where('media_id', '972598765041684170_1544929802')->first();
            $media_id = $InstagramMedia->media_id;
            $media_link = $InstagramMedia->media_link;
            $media_video = $InstagramMedia->media_video;
            $text = $InstagramMedia->media_caption;
            $text = json_decode($text, true);
            //$message = $text['text'];
            $message = $text;
            $message = $message . '
// Ссылка на Инстаграм: ' . $media_link;

            //  https://graph.facebook.com/1838005206523033/feed
            //https://developers.facebook.com/tools/explorer/?method=POST&path=1838005206523033%2Ffeed&version=v2.9&message=%D0%A2%D0%B5%D1%81%D1%82%D0%BE%D0%B2%D0%BE%D0%B5%20%D1%81%D0%BE%D0%BE%D0%B1%D1%89%D0%B5%D0%BD%D0%B8%D0%B5&link=www.instagram.com%2Fmom_lena%2F
            //$fb_addr = 'https://graph.facebook.com/';
            //$addr = $fb_addr . $this->group_id_fb.'/feed'; //На этот адрес нужно делать POST-запрос с полем message, содержащим сообщение
            //$added_strs = '?message=Test&link=www.instagram.com/mom_lena/';
            //Но лучше пользоваться готовым Фейсбук фреймворком


            if ($this->isUrlExist($media_link))
            {
                //Facebook-авторизация
                $fb = new \Facebook\Facebook([
                    'app_id' => $this->facebook_app_id,
                    'app_secret' => $this->facebook_app_secret,
                    'default_graph_version' => 'v2.9',
                ]);

                /*
                            //Facebook-получение своего имени
                            try {
                                // Returns a `Facebook\FacebookResponse` object
                                $response = $fb->get('/me?fields=id,name', $this->facebook_app_access_token);
                            } catch(\Facebook\Exceptions\FacebookResponseException $e) {
                                echo 'Graph returned an error: ' . $e->getMessage();
                                exit;
                            } catch(\Facebook\Exceptions\FacebookSDKException $e) {
                                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                                exit;
                            }
                            $user = $response->getGraphUser();
                            echo 'Name: ' . $user['name'];*/


                //Получаем бесконечный токен для группы на основании временного токена администратора
                /*$user_short_lived_token = '';
                $extended_token = $this->FacebookGetTokenFrom1HourTo60Days($user_short_lived_token);
                $unlimited_token = $this->FacebookGetTokenFrom60DaysToUnlimited($extended_token, $this->facebook_group_id);
                echo $unlimited_token;*/


                //Загрузка ВИДЕО на наш сервер
                if ($media_video != NULL)
                {
                    $facebook_destiny = 'videos';
                    // Скачиваем видео с внешнего сервера на свой сервер
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $media_video);
                    curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
                    $curl_result = curl_exec($ch);
                    curl_close($ch);
                    $file_path = __DIR__ . '/temp/' . basename($media_video);
                    //echo '<br>$file_path>>>>>'.$file_path;
                    $fp = fopen($file_path, 'w');
                    fwrite($fp, $curl_result);
                    fclose($fp);


                    //Делаем Caption для видео-ролика - обрезаем лишнее и делаем троеточие (максимум символов в ВК: 128)
                    $caption = $message;
                    if (strlen($caption) > 62)
                    {
                        $caption = substr($caption, 0, 62);
                        $caption = rtrim($caption, "!,.-");
                        $caption = substr($caption, 0, strrpos($caption, ' '));
                        $caption = $caption . '...';
                    }
                    echo '$file_path>>>>>' . $file_path . '<<<<<<';

                    //Facebook - загрузка фото/видео
                    $data = [
                        'title' => $caption,
                        'description' => $message,
                        'source' => $fb->fileToUpload($file_path),
                    ];
                } //Загрузка Фото на наш сервер
                else
                {
                    $facebook_destiny = 'photos';
                    $media_image = $this->GetFullPhotoOfInstagram($InstagramMedia->media_image);
                    $file_path = __DIR__ . '/temp/' . basename($media_image);
                    file_put_contents($file_path, file_get_contents($media_image));
                    //Facebook - загрузка фото/видео
                    $data = [
                        'message' => $message,
                        'source' => $fb->fileToUpload($file_path),
                    ];
                }

                //Загрузка файла в Фейсбук
                try
                {
                    // Returns a `Facebook\FacebookResponse` object
                    $response = $fb->post('/me/' . $facebook_destiny, $data, $this->facebook_app_access_token);
                } catch (\Facebook\Exceptions\FacebookResponseException $e)
                {
                    echo 'Graph returned an error: ' . $e->getMessage();
                    exit;
                } catch (\Facebook\Exceptions\FacebookSDKException $e)
                {
                    echo 'Facebook SDK returned an error: ' . $e->getMessage();
                    exit;
                }
                if (isset($response))
                {
                    $graphNode = $response->getGraphNode();
                    $res = $graphNode['id'];
                    echo '<br>Photo/Video ID: ' . $res. '<br>';
                } else
                {
                    $this->SaveStatus(98,
                        'ERROR! Instagram media ' . $media_link . ' not uploaded to Facebook. Some error occurred',
                        $this->bot_id,
                        $media_link
                    );
                }

                //Перед удалением файла, нужно очистить все переменные, чтобы разлочить файл
                $data = [];
                $response = '';
                $graphNode = '';
                $fb = '';
                unlink($file_path);

                $InstagramMedia->fb_published_date = $this->today;
                $InstagramMedia->Update();
                $this->SaveStatus(98,
                    'SUCCESS! Instagram media ' . $media_link . ' is successfully published to Facebook: https://www.facebook.com/' . $this->facebook_group_id . '_' . $res,
                    $this->bot_id,
                    $media_link
                );
            }
            else //if ($this->isUrlExist($media_link))
            {
                $InstagramMedia->delete(); //удаляем, если не найдено
            }
        }
        return $res;
    }

    protected $facebook_group_id = '1838005206523033'; //ID группы Мама Лена
    //Токен, полученный для приложения для редактирования группы Мама Лена
    protected $facebook_app_access_token = '';
    protected $facebook_app_id = ''; //ID созданного приложения
    protected $facebook_app_secret = ''; //Секретный код приложения


    /*
     * Метод для публикации видео из базы данных сайта на Ютуб канал по очереди для тех записей, которые ещё не опубликованы (NULL)
     */
    public function PublishToYoutube()
    {
        $res = 0;

        //Выбираем те записи, которые ещё не опубликованы
        $InstagramMedia_NotPublished_Count = InstagramMedia::whereNull('youtube_published_date')->
        whereNotNull('media_video')->
        OrderBy('media_created_time', 'asc')
            ->count();

        if ($InstagramMedia_NotPublished_Count > 0)
        {
            $InstagramMedia = InstagramMedia::whereNull('youtube_published_date')->
            whereNotNull('media_video')->
            OrderBy('media_created_time', 'asc')
                ->first();
            //$InstagramMedia = InstagramMedia::where('media_id', '972598765041684170_1544929802')->first();

            $media_link = $InstagramMedia->media_link;
            $media_video = $InstagramMedia->media_video;
            $text = $InstagramMedia->media_caption;
            $text = json_decode($text, true);
            //$message = $text['text'];
            $message = $text;

            //Убираем смайлики :joy: из текста - в описании к видео они НЕ преобразуются
            //http://uvsoftium.ru/php/regexp.php - регулярные выражения онлайн
            $pattern = '/(:[A-z]*:)/';
            $replacement = '';
            $message = preg_replace($pattern, $replacement, $message);

            //Добавляем ссылку на Инстаграм
            $message = $message . '
// Ссылка на Инстаграм: ' . $media_link;

            //Загрузка ВИДЕО на наш сервер
            if ($media_video != NULL)
            {

                // Скачиваем видео с внешнего сервера на свой сервер
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $media_video);
                curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
                $curl_result = curl_exec($ch);
                curl_close($ch);
                $file_path = __DIR__ . '/temp/' . basename($media_video);
                //echo '<br>$file_path>>>>>'.$file_path;
                $fp = fopen($file_path, 'w');
                fwrite($fp, $curl_result);
                fclose($fp);


                //Делаем Caption для видео-ролика - обрезаем лишнее и делаем троеточие
                $caption = $message;
                if (strlen($caption) > 120)
                {
                    $caption = substr($caption, 0, 120);
                    $caption = rtrim($caption, "!,.-");
                    $caption = substr($caption, 0, strrpos($caption, ' '));
                    $caption = $caption . '...';
                }
                //echo '$file_path>>>>>'.$file_path;

                $GoogleClass = new Google();
                $tags = array("Дети", "Воспитание", "Многодетная мама", "Мама 4 детей");
                //$GoogleClass->UploadVideo('', '', $message, $tags);
                $res = $GoogleClass->UploadVideo3($file_path, $caption, $message, $tags);
                //$GoogleClass->getToken();

                $InstagramMedia->youtube_published_date = $this->today;
                $InstagramMedia->Update();
                $this->SaveStatus(97,
                    'SUCCESS! Instagram media ' . $media_link . ' is successfully published to Youtube: https://www.youtube.com/watch?v=' . $res,
                    $this->bot_id,
                    $media_link
                );
            }

        }
        return $res;
    }



    /*
     *
     * ГЛАВНАЯ ФУНКЦИЯ запуска бота "ЛЕНА ИНСТАГРАМ"
     *
     */
    public function Run()
    {
        $this->bot_id = $this->UpdateGeneralInfo(self::__BOT_NAME);

        $this->SaveStatus(1,'Bot Run>>>>', $this->bot_id);

        $last_scanned_id = $this->GetDataForUser(); //Получаем новые данные со страницы Лены из Инстаграма и заносим их в БД

        //Проверяем, остались ли ещё пункты для поиска
        if ($last_scanned_id == '')
        {
            $this->PublishToVK(); //публикуем в ВК то, что ещё не опубликовано из БД (одна запись за раз)
            $this->PublishToFaceBook(); //публикуем в Фейсбук то, что ещё не опубликовано из БД (одна запись за раз)
            $this->PublishToYoutube(); //Публикуем в Ютуб
        }

        $this->SaveStatus(1,'<<<<Bot Ended', $this->bot_id);


        //ИНТСАГРАММ ЛЕНА-БОТ
        //$InstagramLenaBot = new InstagramLenaBot(4);
        //$InstagramLenaBot->UploadVideoFromInstagramToVK(''); //загрузить видео в ВК
        //$InstagramLenaBot->PublishToVK(); //Публикуем в ВК
        //$InstagramLenaBot->PublishToFaceBook(); //Публикуем в Фейсбук (тестирование загрузки видео)
        //$InstagramLenaBot->PublishToYoutube(); //Публикуем в Ютуб (тестирование загрузки видео)

    }


}

