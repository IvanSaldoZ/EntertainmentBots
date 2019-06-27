<?php namespace Cwtuning\Bots;


use System\Classes\PluginBase;
use Backend;

class Plugin extends PluginBase
{

    public function pluginDetails()
    {
        return [
            'name'        => 'Bots',
            'description' => 'Bots (Crawlers) System of finding interesting pages on the Web',
            'author'      => 'Cwtuning',
            'icon'        => 'icon-android'
        ];
    }



    public function registerSettings()
    {
    }


    public function registerComponents()
    {
        return [
            '\Cwtuning\Bots\Components\BotRunningSingle' => 'BotRunningSingle',
            '\Cwtuning\Bots\Components\BotRunning' => 'BotRunning',
            '\Cwtuning\Bots\Components\BotPostPublish' => 'BotPostPublish',
            '\Cwtuning\Bots\Components\BotPostEditText' => 'BotPostEditText',
            '\Cwtuning\Bots\Components\BotPostsShow' => 'BotPostsShow',
            '\Cwtuning\Bots\Components\BotPostShowSingle' => 'BotPostShowSingle',
            '\Cwtuning\Bots\Components\ServiceBestCommentDaily' => 'ServiceBestCommentDaily',
            '\Cwtuning\Bots\Components\ServiceBestTags' => 'ServiceBestTags',
        ];
    }

}
