<?php namespace Cwtuning\Bots\Classes\Bots;

use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\BestTag as BestTagModel;
use RainLab\Blog\Models\Post as BlogPost;



class ServiceBestTags extends Bot
{

    const __BOT_NAME = 'ServiceBestTags';
    const __SITE_NAME = 'Service Bot Best Tags Daily'; //Отобажаемое название сайта



    /*
     *
     * Метод для нахождения самых популярных тэгов
     *
     */
    protected function FindTheMostPopularTags()
    {


        //Получаем тэги последних 100 постов для того, чтобы получить тэги
        $Posts = BlogPost::take(100)->orderBy('id', 'desc')->get(); //получаем ID последних постов

        $tag_count = [];
        foreach ($Posts as $Post)
        {
            foreach ($Post->tags as $tag)
            {
                $slug = $tag->name;
                if (array_key_exists($slug, $tag_count))
                {
                    $tag_count[$slug] = $tag_count[$slug] + 1;
                }
                else
                {
                    $tag_count[$slug] = 1;
                }
            }
        }
        arsort($tag_count); //Сортируем массив в обратном порядке
        //var_dump($tag_count);

        //Сначала очищаем таблицу лучших тэгов
        BestTagModel::truncate();
        //Затем добавляем туда 10 лучших тэгов с указанием, сколько постов имеется с данным тэгом
        $i = 1;
        foreach ($tag_count as $tag_name => $tag_counter)
        {
            $BestTagNew = new BestTagModel();
            $BestTagNew->tag_name = $tag_name;
            $BestTagNew->post_count = $tag_counter;
            $BestTagNew->Save();
            $i++;
            if ($i > 10) break;
        }

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

        //Ищем самый популярный коммент
        $this->FindTheMostPopularTags();

        //Завершение
        $this->SaveStatus(1, '<<<<Bot Ended', $this->bot_id);

    }


}