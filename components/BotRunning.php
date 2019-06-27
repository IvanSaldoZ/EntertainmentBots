<?php namespace Cwtuning\Bots\Components;


use Cms\Classes\ComponentBase;
use Cwtuning\Bots\Classes\BotsRunning as BotInterface;
use Cwtuning\Bots\Classes\Bot as BotMain;
use Carbon\Carbon;




class BotRunning extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name' => 'Bot Running',
            'description' => 'Attach this component to the page where all bot starts',
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
        echo 'onRun of BotRunning is Init()<br>';
        $this->StartAllBots();
    }



    /*
     * Start Bot for getting info
     *
     */
    protected function StartAllBots()
    {
        $BotMain = new BotMain();
        $hour_now = $BotMain->GetHourNow();
        $Bots = $BotMain->BeforeAll(); //получаем список всех включенных ботов

        //var_dump($Bots);
        foreach ($Bots as $Bot1)
        {
            $minutes_between_current = $this->GetMinutesToDateTime($Bot1->last_scanned);
            if (($minutes_between_current >= $Bot1->minutes_between)
                AND ($hour_now >= $Bot1->hours_begin)
                AND ($hour_now <= $Bot1->hours_end))
            {

                $Bot = new BotInterface();
                try
                {
                    $Bot->BotRun($Bot1->title);
                }
                catch (\Exception $e)
                {
                    echo '<br>Выброшено исключение: ',  $e->getMessage(), "\n";
                    $BotMain->SaveStatus(5555, $e->getMessage(), $Bot1->id); //Сохраняем ошибку в БД
                }
                echo $Bot1->title.' был успешно запущен!<br>';
            }
        }


    }





    /*
     *
     * Находим разницу в минутах до даты в формате 2017-05-06 22:35:40
     *
     */
    protected function GetMinutesToDateTime($dateTime)
    {
        //echo $Bot1->minutes_between;
        //echo $Bot1->last_scanned;
        //$minutes_between_current = ($Bot1->last_scanned) - (Carbon::now(+3)->toDateTimeString());
        //echo Carbon::createFromTimestamp(0)->gte($Bot1->last_scanned);
        $date_of_scan = Carbon::createFromFormat('Y-n-j G:i:s',$dateTime);
        $date_of_scan_timestamp = $date_of_scan->timestamp;
        $today = Carbon::now();
        $today_timestamp = $today->timestamp;
        return intval(($today_timestamp - $date_of_scan_timestamp)/60);
    }








    
}
