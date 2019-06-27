<?php namespace Cwtuning\Bots\Classes\Bots;


use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\Bot as BotModel;
use RainLab\Blog\Models\Post as BlogPost;
use Db;
use Cwtuning\Social\Components\BotPostExportVK;


/*
 *
 * Бот для добавления новостей с сайта Шутеечка.Ру в различные социальные сети, такие как ВК
 *
 */

class BotSocialExporterVK extends Bot
{

    const __BOT_NAME='SocialExporter_VK_Bot';


    /*
     *
     * Получаем статью для публикации и публикуем её
     *
     */
    protected function GetPostToPublish($id)
    {
        $PostToPublish = BlogPost::where('id', '>', $id)
            ->where('published', 1)
            ->OrderBy('id', 'asc')
            ->take(1)
            ->first();
        //$TheSameArticlesCount = 0;
        if (isset($PostToPublish))
        {

            $post_id = $PostToPublish->id; //теперь в ID находится новый ID
            echo $post_id;

            $ExportToVK_Bot = new BotPostExportVK();
            //возвращаем ID последнего опубликованного поста
            $ExportToVK_Bot->doPostExportToVK($post_id);
            $res_msg = $post_id;

        }
        else
        {
            //если ничего не опубликовали - то просто возвращаем ID, который уже есть
            $res_msg = $id;
        }
        return $res_msg;
    }



    /*
     *
     * Главная функция бота "Экспорт записей в ВК"
     *
     */
    public function Run()
    {

        $this->bot_id = $this->UpdateGeneralInfo(self::__BOT_NAME);

        $this->SaveStatus(1,'Bot Run>>>>', $this->bot_id);

        $Bots = BotModel::where('title', self::__BOT_NAME)->first();

        $last_scanned_id = $Bots->last_scanned_id;

        //$BotSocialExporterVK = new BotSocialExporterVK();

        $last_scanned_id = $this->GetPostToPublish($last_scanned_id);

        $Bots->last_scanned_id = $last_scanned_id;

        $Bots->Update();

        $this->SaveStatus(1,'<<<<Bot Ended', $this->bot_id);



        //Публикация в ВК
        /*  $BotSocialExporterVK = new BotSocialExporterVK();
            $last_scanned_id = $BotSocialExporterVK->GetPostToPublish(9);
            echo '$last_scanned_id='.$last_scanned_id;
            $ExportToVK_Bot = new BotPostExportVK();
            $ExportToVK_Bot->doPostExportToVK(5);
        */



    }


}