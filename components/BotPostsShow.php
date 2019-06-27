<?php namespace Cwtuning\Bots\Components;


use Cms\Classes\ComponentBase;
use RainLab\Blog\Models\Post as BlogPost;
use Cwtuning\Bots\Models\Article;
use RainLab\Blog\Models\Category;
use Cwtuning\Userbotsspecial\Components\IsAdmin;
use Db;
use Auth;


class BotPostsShow extends ComponentBase
{

    public $BotPosts;

    public function componentDetails()
    {
        return [
            'name' => 'Bot Posts Show',
            'description' => 'Show the posts that Bots have found on the page of a Website',
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
        //echo 'Test OK<br>';
        $this->BotPosts = $this->LoadPostsOfBots();
    }



    /*
     * Loading posts of Bots
     *
     */
    protected function LoadPostsOfBots()
    {
        //Является ли пользователь администратором
        $isAdmin = new IsAdmin();

        //Передаем список постов, найденных Ботами
        $BotArticle_model = new Article();
        if ($isAdmin->GetIsAdmin()) {
            $res = $BotArticle_model->all();
        }
        else
        {
            $res = $BotArticle_model->where('is_published', 1);
        }
        return $res;
    }





}