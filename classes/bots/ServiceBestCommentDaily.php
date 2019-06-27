<?php namespace Cwtuning\Bots\Classes\Bots;

use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\Article as ArticleModel;
use Cwtuning\Bots\Models\ArticleComment as ArticleCommentModel;
use Cwtuning\Bots\Models\BestCommentDaily as BestCommentDailyModel;

use RainLab\Blog\Models\Post as BlogPost;

use Carbon\Carbon;


class ServiceBestCommentDaily extends Bot
{

    const __BOT_NAME = 'ServiceBestCommentDaily';
    const __SITE_NAME = 'Service Bot Best Comment Daily'; //Отобажаемое название сайта



    /*
     *
     * Метод для нахождения самого популярного коммента
     *
     */
    protected function FindTheMostPopularComment()
    {

        $res = -1;

        $BestCommentDaily = ArticleCommentModel::where('article_id', '<>', '-1')
            ->orderBy('like_count', 'desc')
            ->first();

        if (isset($BestCommentDaily))
        {
            if ($BestCommentDaily->like_count >= $this->comment_rating_minimum)
            {
                $ArticleID = $BestCommentDaily->article_id;
                $PostID = ArticleModel::where('id', $ArticleID)->pluck('post_id')->first(); //получаем сначала ID поста...
                $PostSlug = BlogPost::where('id',$PostID)->pluck('slug')->first(); //...чтобы получить его slug

                if ($PostSlug != NULL)
                {
                    $BestCommentDailyNew = new BestCommentDailyModel();
                    $BestCommentDailyNew->article_id = $ArticleID;
                    $BestCommentDailyNew->post_id = $PostID;
                    $BestCommentDailyNew->post_slug = $PostSlug;
                    $BestCommentDailyNew->comment = $BestCommentDaily->comment;
                    $BestCommentDailyNew->author_display_name = $BestCommentDaily->author_display_name;
                    $BestCommentDailyNew->author_profile_image_url = $BestCommentDaily->author_profile_image_url;
                    $BestCommentDailyNew->author_profile_url = $BestCommentDaily->author_profile_url;
                    $BestCommentDailyNew->like_count = $BestCommentDaily->like_count;
                    $BestCommentDailyNew->published_at = $BestCommentDaily->published_at;
                    $BestCommentDailyNew->unique_id = $BestCommentDaily->unique_id;
                    $BestCommentDailyNew->link = $BestCommentDaily->link;
                    $BestCommentDailyNew->best_date = Carbon::today();
                    $BestCommentDailyNew->Save();
                    //Очищаем таблицу со всеми комментариями
                    ArticleCommentModel::truncate();
                    $res =  $PostSlug;
                }
                else
                {
                    $BestCommentDaily->delete();
                }
            }
        }

        return $res;

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
        $link_to_post =  $this->FindTheMostPopularComment();
        if ($link_to_post > -1)
        {
            echo 'http://shuteechka.ru/show_news/'.$link_to_post;
        }
        else
        {
            echo 'Ни одного комментария для рейтинга не найдено в базе данных';
        }

        //Завершение
        $this->SaveStatus(1, '<<<<Bot Ended', $this->bot_id);

    }


}