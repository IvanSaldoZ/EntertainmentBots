<?php namespace Cwtuning\Bots\Components;


use Cms\Classes\ComponentBase;
use RainLab\Blog\Models\Post as BlogPost;
//use Cwtuning\Bots\Classes\PostModelExt as BlogPost;
use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\Article;
use Cwtuning\Bots\Models\Bot as BotModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use RainLab\Blog\Models\Category;
use Db;


class BotPostPublish extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Bot Publish Button',
            'description' => 'Show the button to Publish the Post into the Blog table',
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
        // $this->onBotPostPublish();

    }



    /*
     * Publish it to the Blog
     *
     */
    public function onBotPostPublish()
    {
        $today = Carbon::now();
        $post_id = intval(Input::get('post_id'));
        //$post_id = 25;

        $BotArticle = new Article();
        $NewPost = new BlogPost();
        $BotModel = new BotModel();

        //$NewCategory = new Category();

        $post = $BotArticle->whereId($post_id)->first();
        $TheSameArticlesCount = $NewPost->where('title', $post->title)->count();
        $pivot_table = $NewPost->belongsToMany['categories']['table']; //Table for adding ralations between posts and categories
//        $pivot_table_of_Aricle_Bot = $BotArticle->belongsToMany['bot']['table']; //Table for ralations between article and a Bot
        $pivot_table_of_Bot_Cat = $BotModel->belongsToMany['categories']['table']; //Table for ralations between bot and categories
        //$TheSameArticlesCount = 0;
        if ($TheSameArticlesCount == 0)
        {
//            $BotID = Db::table($pivot_table_of_Aricle_Bot)->where('article_id', $post_id)->pluck('bot_id'); //Get the Bot ID
            $BotID = $post->bot_id;
            $BotCatID = Db::table($pivot_table_of_Bot_Cat)->where('bot_id', $BotID)->pluck('category_id'); //Get the Category ID to add in the New Post
            $NewPost -> id = NULL;
            $NewPost -> user_id = 2;
            $NewPost -> title = $post->title;
            $NewPost -> excerpt = '';
            $NewPost -> content = $post->content;
            $NewPost -> content_html = $post->content;
            $NewPost -> published = 1;
            $NewPost -> published_at = $today;
            $NewPost -> created_at = $today;
            $NewPost -> updated_at = $today;
            $NewPost -> slug = Str::slug($post->title);
            //$NewPost -> slug = ;*/
            /*echo $post->title;
            $NewPost->firstOrCreate(
                [
                    'user_id'=>2,
                    'title'=>$post->title,
                    'excerpt'=>'',
                    'content'=>$post->content,
                    'content_html'=>$post->content,
                    'published_at'=>$today,
                    'published'=>'1',
                    'created_at'=>$today,
                    'slug'=>Str::slug($post->title)
                ]
            );*/
            $NewPost->Save();

            //$NewPost = $NewPost->where('title', $post->title)->first();
            //echo $NewPost;
            //$NewPost->slugAttributes(); //Преобразовываем title в Slug автоматически
            $new_id = $NewPost->id;


            //Adding connection (ralation) between added post and the category
            Db::table($pivot_table)->insert(
                ['post_id' => $new_id, 'category_id' => $BotCatID]
            );
            $res_msg = 'Новость опубликована в блоге успешно! <a href="/show_news/'.$NewPost -> slug.'">Перейти в новость в блоге.</a>';
            $post->is_published = 1;
            $post->published_at = $today;
            $post->post_id = $new_id;
            $post->save();
        }
        else
        {
            $res_msg = 'ОШИБКА: Такая новость уже опубликована!';
        }
        return [
            //'#res' =>
            '#res' => $res_msg
            //'#res' => $this->renderPartial('buttonpostadd.htm')
        ];

    }



}