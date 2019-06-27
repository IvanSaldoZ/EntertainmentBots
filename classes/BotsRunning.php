<?php namespace Cwtuning\Bots\Classes;

//Подключенные боты
use Cwtuning\Bots\Classes\Bots\BotSoftportal;
use Cwtuning\Bots\Classes\Bots\BotInstagramLena;
use Cwtuning\Bots\Classes\Bots\BotYouTuber;
use Cwtuning\Bots\Classes\Bots\BotPikabu;
use Cwtuning\Bots\Classes\Bots\BotSocialExporterVK;
use Cwtuning\Bots\Classes\Bots\BotHabrahabr;
use Cwtuning\Bots\Classes\Bots\ServiceBestCommentDaily;
use Cwtuning\Bots\Classes\Bots\BotGeektimes;
use Cwtuning\Bots\Classes\Bots\ServiceBestTags;
use Cwtuning\Bots\Classes\Bots\BotPsyPractice;
use Cwtuning\Bots\Classes\Bots\BotKg;
use Cwtuning\Bots\Classes\Bots\BotSecretMagNews;
use Cwtuning\Bots\Classes\Bots\BotFishkiNet;


/*
 *
 * Класс для вызова созданных ботов
 *
 */
class BotsRunning
{
    /*
     *
     * Запуск конкретного бота
     *
     */
    public function BotRun($BotName)
    {

        switch ($BotName)
        {
            case 'SoftportalBot':               $Bot = new BotSoftportal(); break;          //СОФТПОРТАЛ - НОВОСТИ О НОВЫХ ВЕРСИЯХ ПРОГРАММ
            case 'InstagramLenaBot':            $Bot = new BotInstagramLena(); break;       //ЛЕНА ИНСТАГРАМ
            case 'YouTuberAddAllBot':           $Bot = new BotYouTuber(); break;            //YOUTUBER - добавляем все видео в блог
            case 'BotPikabu':                   $Bot = new BotPikabu(); break;              //PIKABU_BOT - добавляем посты с горячего и лучшего с Пикабу
            case 'SocialExporter_VK_Bot':       $Bot = new BotSocialExporterVK(); break;    //SOCIAL_EXPORTER_BOT - добавляем посты в группу ВК
            case 'Habrahabr':                   $Bot = new BotHabrahabr(); break;           //Habrahabr
            case 'ServiceBestCommentDaily':     $Bot = new ServiceBestCommentDaily(); break;//Service - лучший за день коммент
            case 'Geektimes':                   $Bot = new BotGeektimes(); break;           //Geektimes
            case 'ServiceBestTags':             $Bot = new ServiceBestTags(); break;        //ServiceBestTags - популярные тэги за неделю
            case 'PsyPractice':                 $Bot = new BotPsyPractice(); break;         //PsyPractice - новости с сайта по психологии
            case 'kg':                          $Bot = new BotKg(); break;                  //Kg - новости с сайта Kino-govno.com
            case 'SecretMagNews':               $Bot = new BotSecretMagNews(); break;       //SecretMagNews - новости с сайта СекретФирмы
            case 'FishkiNet':                   $Bot = new BotFishkiNet(); break;       //FishkiNet - новости с сайта Фишки.Net

            default:                            $Bot = new BotPikabu();                     //по умолчанию
        }

        //Запускаем бот
        $Bot->Run();

    }



}