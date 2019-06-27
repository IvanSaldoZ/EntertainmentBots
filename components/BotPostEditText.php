<?php namespace Cwtuning\Bots\Components;

/*
 * Bots by Ivan Saldikov (c) 2017
 * saldoz@ya.ru
 *
 */

use Cms\Classes\ComponentBase;
use RainLab\Blog\Models\Post as BlogPost;
//use Cwtuning\Bots\Classes\PostModelExt as BlogPost;
use Cwtuning\Bots\Classes\Bot;
use Cwtuning\Bots\Models\Article;
use Cwtuning\Bots\Models\Bot as BotModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;


class BotPostEditText extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Bot Edit Text Button',
            'description' => 'Show the button to edit text of the post before its publishing',
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
       // echo 'Test OK Edit text<br>';
      
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
                    <textarea name="new_cont" cols="120" rows="15">'.$cont.'</textarea>
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
            '#EditTextBtnCancel' => '',
            '#EditTextBtn' => '<button type="submit" class="btn btn-default">Отредактировать текст</button>',
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

}