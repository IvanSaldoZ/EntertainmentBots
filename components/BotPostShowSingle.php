<?php namespace Cwtuning\Bots\Components;


use Cms\Classes\ComponentBase;
use RainLab\Blog\Models\Post as BlogPost;
use Cwtuning\Bots\Models\Bot as BotModel;
use Cwtuning\Bots\Models\Article;
use RainLab\Blog\Models\Category;
use Cwtuning\Userbotsspecial\Components\IsAdmin;
use Db;
use Auth;
use Input;
use Str;
use Carbon\Carbon;



class BotPostShowSingle extends ComponentBase
{

    public function componentDetails()
    {

        return [
            'name' => 'Bot Post Show Single',
            'description' => 'Show on the page the single post that Bots have found in the Web',
        ];

    }


    public function defineProperties()
    {

        return [
            'slug' => [
                'title'       => 'Slug',
                'description' => 'Укажите slug, по которому будет находиться статья (по умолчанию: {{ :slug }})',
                'default'     => '{{ :slug }}',
                'type'        => 'string'
            ],
        ];

    }


    /*
     * Before Running the component
     *
     */
    public function onRun()
    {

        $this->BotPost = $this->LoadBotPost();

    }



    /*
     * Loading posts of Bots
     *
     */
    protected function LoadBotPost()
    {
        $slug = $this -> property('slug');

        $isAdmin = new IsAdmin();

        //Передаем список постов, найденных Ботами
        $BotArticle = new Article();
        $BotArticle = $BotArticle->where('slug', $slug)->first();

        if ($isAdmin->GetIsAdmin()) {
            $res = $BotArticle;
        }
        else
        {
            $res = '';
        }

        return $res;
    }



    /*
     * Edit text show textarea
     *
     */
    public function onBotPostEditText()
    {
        $post_id = intval(Input::get('post_id'));
        $this->res = $post_id;
        $BotArticle = new Article();
        $post = $BotArticle->whereId($post_id)->first();
        $cont = $post->content;
        return [
            '#EditTextBtnCancel' => '
                                <form data-request="onBotPostEditTextCancel">
                                    <input type="hidden" name="post_id" value="{{ record.id }}">
                                    <p>
                                        <button type="submit" class="btn btn-danger  btn-lg">Отмена</button>
                                    </p>
                                </form>
                                ',
            '#EditTextBtn1' => '<button type="submit" class="btn btn-default disabled" disabled>Отредактировать текст</button>',
            '#text_field' => '
                    <textarea name="new_cont" cols="80" rows="10">'.$cont.'</textarea>
                    <p>
                        <button type="submit" class="btn btn-success btn-lg btn-block">Сохранить</button>
                    </p>
                ',
        ];
    }


    /*
     * Edit text run the changes into the database
     *
     */
    public function onBotPostEditTextDo()
    {
        $post_id = intval(Input::get('post_id'));
        $new_cont = Input::get('new_cont');
        $BotArticle = new Article();
        $post = $BotArticle->whereId($post_id)->first();
        $post->content = $new_cont;
        $post->save();
        return [
            '#text_field' => ' ',
            '.content' => $new_cont,
            '#EditTextBtn' => '<button type="submit" class="btn btn-default">Отредактировать текст</button>',
            '#EditTextBtnCancel' => '',
        ];
    }


    /*
     * Cancel editing text
     *
     */
    public function onBotPostEditTextCancel()
    {
        return [
            '#text_field' => ' ',
            '#EditTextBtn' => '<button type="submit" class="btn btn-default">Отредактировать текст</button>',
            '#EditTextBtnCancel' => '',
        ];
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
    
    
    
    

    

    public $BotPost;



}