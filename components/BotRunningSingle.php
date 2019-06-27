<?php namespace Cwtuning\Bots\Components;

use Cms\Classes\ComponentBase;
use Cwtuning\Bots\Classes\Bots\BotKg;
use Cwtuning\Bots\Classes\Bots\BotPikabu;
use Cwtuning\Bots\Classes\BotsRunning as BotInterface;
use Cwtuning\Bots\Classes\Bot as BotMain;



class BotRunningSingle extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name' => 'Bot Running Single',
            'description' => 'Attach this component to the page where a single bot starts (for testing purposes)',
        ];
    }




    public function defineProperties()
    {
        return [];
    }




    /*
     * Before Running the component
     *
     */
    public function onRun(){
        echo 'OK... onRun of BotRunningSingle is Init1()<br>';
        $this->StartSinlgleBot();
    }




    /*
     * Function for const Pi calculation
     *
     */
    protected function PiCalc()
    {
        //Метод Монте-Карло - количество разыгрываемых точек
        $Ntot = 100000;

        //Точность по оси x и y
        $epsilon = 10000;

        //Количество точек, попавших внутрь круга
        $Nin = 0;

        for ($i = 0; $i <= $Ntot; $i++)
        {
            //Смотрим, куда она упала
            $x = rand(-$epsilon, $epsilon);
            $y = rand(-$epsilon, $epsilon);
            $x = $x / $epsilon;
            $y = $y / $epsilon;

            $r2 = $x*$x + $y*$y;
            //$r = sqrt($x*$x + $y*$y);

            //Считаем кол-во точек, попавших внутрь круга (эквивалент площади: S=Pi*r2)
            if ($r2 <= 1)
            {
                $Nin++;
            }

        }

        //Теперь делим получившееся количество точек, попавшее внутрь круга, к количеству всех разыгранных точек
        //(они все попали внутрь квадрата), площадь которого равна 4
        $res = 4*$Nin/$Ntot;

/*        echo '$x = '.$x;
        echo '<br>$y = '.$y;
        echo '<br>$r2 = '.$r2;
*/

        return $res;

    }




    /*
     * Start Bot for getting info
     *
     */
    protected function StartSinlgleBot()
    {

        //phpinfo();
        //XHProfiler - для отображения слабых (долгих, памятозатратных) функций того или иного php-файла (в нашем случае - бота) - НАЧАЛО

        /*if (extension_loaded('xhprof')) {
            include_once '/var/www/admin_user_mephi/data/www/seller2.ru/new/xhprof/xhprof_lib/utils/xhprof_lib.php';
            include_once '/var/www/admin_user_mephi/data/www/seller2.ru/new/xhprof/xhprof_lib/utils/xhprof_runs.php';
            xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
        }
        */

        //Запускаем конкретного бота
       // $Bot = new BotInterface();
        //$BotName = 'ServiceBestCommentDaily';
        //$Bot->BotRun($BotName);

        echo '<br>'.$this->PiCalc();





/*
        $BotMain = new BotKg();
        $BotMain->PostSaveCommentsKg(1,
            67199,
            15,
            'ochen-plohie-mamochki-2-vtoroj-trejler-bez-cenzury',
            'http://kg.ru');
*/
        /*$BotMain = new BotMain();
        $new_content = $BotMain->ApplyViewToContent('<a target="_blank" href="/trends/whatsup/mozhem-zakrytsya-mikhail-goncharov-teremok-zhaluetsya-na-mcdonald-s-i-tc.htm">снова</a>', 'https://yandex.ru', 'Testing name');
        var_dump($new_content);
        */
        //$res = $BotMain->DownloadLinkToParse('http://kg-portal.ru/comments/67193-novyj-bond-budet-pohozh-na-zalozhnicu/u_popular/#comments'); //Скачиваем страницу новости для парсинга
        //$fp = fopen(__DIR__.'/../classes/bots/temp/temp_file_url.txt', 'wb');
        //fwrite($fp,$res);
        //fclose($fp);



        //XHProfiler - КОНЕЦ - сохраняем результаты тестирования
        /*if (extension_loaded('xhprof')) {
            $profilerNamespace = $BotName;
            $xhprofData = xhprof_disable();
            $xhprofRuns = new \XHProfRuns_Default();
            $runId = $xhprofRuns->save_run($xhprofData, $profilerNamespace);
        }
        */


        //РЕЙТИНГ СТАТЬИ БЛОГА
        //$PostRatingButton = new PostRatingButton();
        //$PostRatingButton->doRatePost(479, -1);
        //https://www.w3schools.com/howto/tryit.asp?filename=tryhow_js_toggle_like_dislike - отсюда взять лайки-дизлайки кнопочки




    }







}
