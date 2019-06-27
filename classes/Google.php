<?php namespace Cwtuning\Bots\Classes;


//Youtube API
require_once __DIR__ . '/../../../../vendor/google/vendor/autoload.php';



class Google
{

    protected $youtube_OAUTH2_CLIENT_ID = '';
    protected $youtube_OAUTH2_SECRET = '';
    protected $youtube_KEY = ''; //Ключ канала Мама Лена
    protected $url = 'https://www.googleapis.com/youtube/v3/';




    /**
     * Делает запрос к Api Youtube (где НЕ нужна авторизация)
     * https://www.googleapis.com/youtube/v3/videoCategories?part=snippet&id=27&key=AIzaSyCX4dpaXo38rudic3eMMUdP-FUDwS5FZyE
     * @param $method (например videoCategories)
     * @param $params (например, part => snippet)
     */
    public function method($method, $params = null, $is_post = false)
    {
        $p = "";
        if( $params && is_array($params) ) {
            foreach($params as $key => $param) {
                $p .= ($p == "" ? "" : "&") . $key . "=" . urlencode($param);
            }
        }
        if (!$is_post)
        {
            //GET-запрос
            $response = file_get_contents($this->url . $method . "?" . ($p ? $p . "&" : "") . "key=" . $this->youtube_KEY);
        }
        else
        {
            //POST-запрос
            $params['key'] = $this->youtube_KEY;
            $ch = curl_init(($this->url).$method);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true );
            curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('content-type text/plain'));
            $response = curl_exec($ch);
            curl_close($ch);
        }

        if( $response ) {
            return json_decode($response);
        }
        return false;
    }




    /*
     * Метод для получения любой информации с Ютуба, которая не требует авторизации
     *
     */
    public function GetYoutubeInfo()
    {

        $params = array(
            'part' => 'snippet',
            'id' => 22,
        );

        //Получаем категории (ID указан выше)
        $obj = $this->method('videoCategories', $params);

        var_dump($obj);
        //$repsonse = $obj['response'];
        //var_dump($repsonse);


    }






    /*
     *
     * Метод для удаления определенного ID видео из списка воспроизведения
     *
     */
    public function YouTubeRemoveVideoFromTheList($playlist_video_id)
    {
        $token_file_path =__DIR__.'/token99999.txt';
        $key = file_get_contents($token_file_path);

        try
        {
            // Client init
            $client = new \Google_Client();
            $client->setClientId($this->youtube_OAUTH2_CLIENT_ID);
            $client->setAccessType('offline');
            $client->setApprovalPrompt('force');
            $client->setAccessToken($key);
            $client->setClientSecret($this->youtube_OAUTH2_SECRET);

            if ($client->getAccessToken())
            {
                /**
                 * Check to see if our access token has expired. If so, get a new one and save it to file for future use.
                 */
                if ($client->isAccessTokenExpired())
                {
                    $newToken = json_decode($key);
                    $client->refreshToken($newToken->refresh_token);
                    $new_token = $client->getAccessToken();
                    //var_dump($new_token);
                    file_put_contents($token_file_path, json_encode($new_token));
                }

                $youtubeService = new \Google_Service_YouTube($client);
                $playlistItems = $youtubeService->playlistItems;
                $deleteVid = $playlistItems->delete($playlist_video_id);

                //$youtube->playlistItems->delete($playlist_video_id);
                echo 'Пункт удален!';
            }
            else
            {
                echo 'ERROR0000: Problems creating the client';
                return '';
            }

        }
        catch(\Google_Service_Exception $e)
        {
            $str = "ERROR1111: Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
            $str .= "Stack trace is ".$e->getTraceAsString();
            echo $str;
            return '';
        }
        catch (\Exception $e)
        {
            $str = "ERROR2222: Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
            $str .= "Stack trace is ".$e->getTraceAsString();
            echo $str;
            return '';
        }




/*        $client = $this->GetClient();
        if ($client != '')
        {
        }*/
        //echo '$clientЮЮЮЮЮЮЮ'.$client;

        //$repsonse = $obj['response'];
        //var_dump($repsonse);
    }





    /*
     *
     * Получаем идентификатор клиента для авторизованных запросов
     *
     */
    public function GetClient()
    {
        $token_file_path =__DIR__.'/token99999.txt';
        $key = file_get_contents($token_file_path);

        try
        {
            // Client init
            $client = new \Google_Client();
            $client->setClientId($this->youtube_OAUTH2_CLIENT_ID);
            $client->setAccessType('offline');
            $client->setApprovalPrompt('force');
            $client->setAccessToken($key);
            $client->setClientSecret($this->youtube_OAUTH2_SECRET);

            if ($client->getAccessToken())
            {
                /**
                 * Check to see if our access token has expired. If so, get a new one and save it to file for future use.
                 */
                if ($client->isAccessTokenExpired())
                {
                    $newToken = json_decode($key);
                    $client->refreshToken($newToken->refresh_token);
                    $new_token = $client->getAccessToken();
                    //var_dump($new_token);
                    file_put_contents($token_file_path, json_encode($new_token));
                }
                return $client;
            }
            else
            {
                echo 'ERROR: Problems creating the client';
                return '';
            }

        }
        catch(\Google_Service_Exception $e)
        {
            $str = "ERROR: Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
            $str .= "Stack trace is ".$e->getTraceAsString();
            echo $str;
            return '';
        }
        catch (\Exception $e)
        {
            $str = "Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
            $str .= "Stack trace is ".$e->getTraceAsString();
            echo $str;
            return '';
        }


    }




    /*
     *
     * Загрузка видео, не используя двухэтапную аутентификацию (можно загружать)
     * http://www.whitewareweb.com/php-youtube-video-upload-google-api-oauth-2-0-v3/
     * http://stackoverflow.com/questions/37887025/php-youtube-api-how-to-allow-user-to-upload-videos-to-my-own-channel
     *
     */
    public function UploadVideo3($videoPath, $title, $description, $tags)
    {
        $token_file_path =__DIR__.'/token99999.txt';
        $key = file_get_contents($token_file_path);

        try{
            // Client init
            $client = new \Google_Client();
            $client->setClientId($this->youtube_OAUTH2_CLIENT_ID);
            $client->setAccessType('offline');
            $client->setApprovalPrompt('force');
            $client->setAccessToken($key);
            $client->setClientSecret($this->youtube_OAUTH2_SECRET);

            if ($client->getAccessToken()) {
                /**
                 * Check to see if our access token has expired. If so, get a new one and save it to file for future use.
                 */
                if($client->isAccessTokenExpired()) {
                    $newToken = json_decode($key);
                    $client->refreshToken($newToken->refresh_token);
                    $new_token = $client->getAccessToken();
                    //var_dump($new_token);
                    file_put_contents($token_file_path, json_encode($new_token));
                }

                $youtube = new \Google_Service_YouTube($client);

                // Create a snipet with title, description, tags and category id
                $snippet = new \Google_Service_YouTube_VideoSnippet();
                $snippet->setTitle($title);
                $snippet->setDescription($description);
                $snippet->setCategoryId('22');
                $snippet->setTags($tags);
                $snippet->setDefaultLanguage("ru");
                $snippet->setDefaultAudioLanguage("ru");

                // Create a video status with privacy status. Options are "public", "private" and "unlisted".
                $status = new \Google_Service_YouTube_VideoStatus();
                $status->setPrivacyStatus("public");
                $status->setPublicStatsViewable(false);
                $status->setEmbeddable(false); // Google defect still not editable https://code.google.com/p/gdata-issues/issues/detail?id=4861

                // Create a YouTube video with snippet and status
                $video = new \Google_Service_YouTube_Video();
                $video->setSnippet($snippet);
                //$video->setRecordingDetails($recordingDetails);
                $video->setStatus($status);

                // Size of each chunk of data in bytes. Setting it higher leads faster upload (less chunks,
                // for reliable connections). Setting it lower leads better recovery (fine-grained chunks)
                $chunkSizeBytes = 1 * 1024 * 1024;

                // Setting the defer flag to true tells the client to return a request which can be called
                // with ->execute(); instead of making the API call immediately.
                $client->setDefer(true);

                // Create a request for the API's videos.insert method to create and upload the video.
                $insertRequest = $youtube->videos->insert("status,snippet,recordingDetails", $video);

                // Create a MediaFileUpload object for resumable uploads.
                $media = new \Google_Http_MediaFileUpload(
                    $client,
                    $insertRequest,
                    'video/*',
                    null,
                    true,
                    $chunkSizeBytes
                );
                $media->setFileSize(filesize($videoPath));

                // Read the media file and upload it chunk by chunk.
                $status = false;
                $handle = fopen($videoPath, "rb");
                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, $chunkSizeBytes);
                    $status = $media->nextChunk($chunk);
                }

                fclose($handle);

                /**
                 * Video has successfully been uploaded, now lets perform some cleanup functions for this video
                 */
                if ($status->status['uploadStatus'] == 'uploaded') {
                    // Actions to perform for a successful upload
                    return $status['id'];
                }

                // If you want to make other calls after the file upload, set setDefer back to false
                $client->setDefer(true);

            } else{
                return 'ERROR: Problems creating the client';
            }

        }
        catch(\Google_Service_Exception $e)
        {
            $str = "ERROR: Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
            $str .= "Stack trace is ".$e->getTraceAsString();
            return $str;
        }
        catch (\Exception $e)
        {
            $str = "Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
            $str .= "Stack trace is ".$e->getTraceAsString();
            return $str;
        }


    }




    /*
     *
     * Получаем токен для метода UploadVideo3 (см. выше)
     * http://www.whitewareweb.com/php-youtube-video-upload-google-api-oauth-2-0-v3/
     *
     */
    public function getToken()
    {
        //Youtube - загрузка видео
        $OAUTH2_CLIENT_ID = $this->youtube_OAUTH2_CLIENT_ID;
        $OAUTH2_CLIENT_SECRET = $this->youtube_OAUTH2_SECRET;

        session_start();
        /*
         * You can acquire an OAuth 2.0 client ID and client secret from the
         * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
         * For more information about using OAuth 2.0 to access Google APIs, please see:
         * <https://developers.google.com/youtube/v3/guides/authentication>
         * Please ensure that you have enabled the YouTube Data API for your project.
         */
        $REDIRECT = filter_var('http://shuteechka.ru/single-bot-run', FILTER_SANITIZE_URL);
        $APPNAME = "Mom Lena Importer Client 1";

        $client = new \Google_Client();
        $client->setClientId($OAUTH2_CLIENT_ID);
        $client->setClientSecret($OAUTH2_CLIENT_SECRET);
        $client->setScopes('https://www.googleapis.com/auth/youtube');
        $client->setRedirectUri($REDIRECT);
        $client->setApplicationName($APPNAME);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');

        // Define an object that will be used to make all API requests.
        $youtube = new \Google_Service_YouTube($client);

        //$code = '4/exfa6qVKQbk8VqxCzXaPRrUHdo8VWrn234kzYX-5RpI#';
        //$client->authenticate($code);
        $token_temp = $client->getAccessToken();

        if (isset($token_temp))
        {
            $client->setAccessToken($token_temp);
            echo "<br><br><br>Access Token: " . json_encode($token_temp).'<br><br><br>';
        }

        //Сначала нет токена, поэтому сначала нужно перейти по ссылке

        // Check to ensure that the access token was successfully acquired.
        if ($client->getAccessToken()) {
            try {
                // Call the channels.list method to retrieve information about the
                // currently authenticated user's channel.
                $channelsResponse = $youtube->channels->listChannels('contentDetails', array('mine' => 'true'));

                $htmlBody = '';
                foreach ($channelsResponse['items'] as $channel) {
                    // Extract the unique playlist ID that identifies the list of videos
                    // uploaded to the channel, and then call the playlistItems.list method
                    // to retrieve that list.
                    $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

                    $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
                        'playlistId' => $uploadsListId,
                        'maxResults' => 50
                    ));

                    $htmlBody .= "<h3>Videos in list $uploadsListId</h3><ul>";
                    foreach ($playlistItemsResponse['items'] as $playlistItem) {
                        $htmlBody .= sprintf('<li>%s (%s)</li>', $playlistItem['snippet']['title'],
                            $playlistItem['snippet']['resourceId']['videoId']);
                    }
                    $htmlBody .= '</ul>';
                }
            } catch (\Google_Service_Exception $e) {
                printf('<p>A service error occurred: <code>%s</code></p>',
                    htmlspecialchars($e->getMessage()));
            } catch (\Google_Exception $e) {
                printf('<p>An client error occurred: <code>%s</code></p>',
                    htmlspecialchars($e->getMessage()));
            }

            $_SESSION['token'] = $client->getAccessToken();
        }
        else
        {
            $state = mt_rand();
            $client->setState($state);
            $_SESSION['state'] = $state;

            $authUrl = $client->createAuthUrl();

            echo $authUrl;
        }
    }











    /*
     *
     * OLD 1 - если функция 3 работать будет, то можно эту функцию можно удалить
     */
    public function UploadVideo($path, $title, $description, $tags)
    {

        //Youtube - загрузка видео
        $OAUTH2_CLIENT_ID = $this->youtube_OAUTH2_CLIENT_ID;
        $OAUTH2_CLIENT_SECRET = $this->youtube_OAUTH2_SECRET;

        echo 'OK11111';
        $path_to_service_account = __DIR__.'/MomLenaImporter-7a59b225fea4.json';
        echo '<br>$path_to_service_account => '.$path_to_service_account.'<br>';
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.$path_to_service_account);
        $client = new \Google_Client();
        $client->setAuthConfig($path_to_service_account);


        //$client->setClientId($OAUTH2_CLIENT_ID);
        //$client->setClientSecret($OAUTH2_CLIENT_SECRET);
        if (!file_exists($path_to_service_account))
        {
            exit;
        }
        $client->useApplicationDefaultCredentials();
        //  $client->setScopes('https://www.googleapis.com/auth/youtube');
        //$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
        //            FILTER_SANITIZE_URL);

        $redirect = filter_var('http://shuteechka.ru/single-bot-run',
            FILTER_SANITIZE_URL);
        //$client->setRedirectUri($redirect);
        //        $client->setApplicationName('Mom Lena Importer Client 1');
        //$client->setDeveloperKey($this->youtube_KEY);
        //$client->setAccessType("offline");
        //$client->setApprovalPrompt('force');
        // $client->setIncludeGrantedScopes(true);
        // $state = '56148151654';
        // $client->setState($state);
        echo '$client=> '.$client->getClientId().'///////';
        //$client->fetchAccessTokenWithAuthCode($this->code);
        //echo $client->getRefreshToken();


        // Define an object that will be used to make all API requests.
        $youtube = new \Google_Service_YouTube($client);

        // Check if an auth token exists for the required scopes
        $tokenSessionKey = 'token-' . $client->prepareScopes();
        echo '$tokenSessionKey=>'.$tokenSessionKey;



        /*        if (isset($_GET['code'])) {
                    if (strval($_SESSION['state']) !== strval($_GET['state'])) {
                        die('The session state did not match.');
                    }

                    $_SESSION[$tokenSessionKey] = $client->getAccessToken();
                    header('Location: ' . $redirect);
                }

                if (isset($_SESSION[$tokenSessionKey])) {
                    $client->setAccessToken($_SESSION[$tokenSessionKey]);
                }*/
        //$client->setAccessToken($tokenSessionKey);
        // $client->authorize();
        // $client->getRefreshToken();
        //echo $client->getAccessToken();

        //        $access_token = $client->getAccessToken();



        echo 'OK4';

        //        echo 'OK555 =>'.$client->getAccessToken();

        if ($client->getAccessToken()) {
            try{

                //                echo 'OK555 =>'.$client->getAccessToken();
                // REPLACE this value with the path to the file you are uploading.
                $videoPath = $path;
                echo '7777777777';


                // Create a snippet with title, description, tags and category ID
                // Create an asset resource and set its snippet metadata and type.
                // This example sets the video's title, description, keyword tags, and
                // video category.
                $snippet = new \Google_Service_YouTube_VideoSnippet();
                $snippet->setTitle($title);
                $snippet->setDescription($description);
                $snippet->setTags($tags);

                // Numeric video category. See
                // https://developers.google.com/youtube/v3/docs/videoCategories/list
                $snippet->setCategoryId("22"); //22 - Люди и блоги

                // Set the video's status to "public". Valid statuses are "public",
                // "private" and "unlisted".
                $status = new \Google_Service_YouTube_VideoStatus();
                $status->privacyStatus = "public";

                // Associate the snippet and status objects with a new video resource.
                $video = new \Google_Service_YouTube_Video();
                $video->setSnippet($snippet);
                $video->setStatus($status);

                // Specify the size of each chunk of data, in bytes. Set a higher value for
                // reliable connection as fewer chunks lead to faster uploads. Set a lower
                // value for better recovery on less reliable connections.
                $chunkSizeBytes = 1 * 1024 * 1024;

                // Setting the defer flag to true tells the client to return a request which can be called
                // with ->execute(); instead of making the API call immediately.
                $client->setDefer(true);

                // Create a request for the API's videos.insert method to create and upload the video.
                $insertRequest = $youtube->videos->insert("status,snippet", $video);

                // Create a MediaFileUpload object for resumable uploads.
                $media = new \Google_Http_MediaFileUpload(
                    $client,
                    $insertRequest,
                    'video/*',
                    null,
                    true,
                    $chunkSizeBytes
                );
                $media->setFileSize(filesize($videoPath));


                // Read the media file and upload it chunk by chunk.
                $status = false;
                $handle = fopen($videoPath, "rb");
                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, $chunkSizeBytes);
                    $status = $media->nextChunk($chunk);
                }

                fclose($handle);

                // If you want to make other calls after the file upload, set setDefer back to false
                $client->setDefer(false);
                return $status['id'];

            } catch (\Google_Service_Exception $e) {
                printf('<p>A service error occurred: <code>%s</code></p>',
                    htmlspecialchars($e->getMessage()));
            } catch (\Google_Exception $e) {
                printf('<p>An client error occurred: <code>%s</code></p>',
                    htmlspecialchars($e->getMessage()));
            }

            $_SESSION[$tokenSessionKey] = $client->getAccessToken();
        }
        else
        {
            // If the user hasn't authorized the app, initiate the OAuth flow
            //   $state = '56148151654';
            // $client->setState($state);
            //            $_SESSION['state'] = $state;
            //$authUrl = $client->createAuthUrl();
            echo '<br><br>ERROR!!! Not authorized';
            //echo '$authUrl=>'.$authUrl;
        }

    }





}