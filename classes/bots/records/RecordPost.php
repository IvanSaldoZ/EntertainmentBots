<?php


namespace Cwtuning\Bots\Classes\Bots\Records;


/*
 *
 * Класс для новостей с парсинговых сайтов
 *
 */
class RecordPost
{
    public $id=''; //ID новости (StoryID)
    public $title=''; //заголовок новости
    public $link=''; //ссылка на источник (на новость)
    public $excerpt=''; //выдержка из новости (краткое содержание)
    public $content=''; //содержание новости
    public $timestamp=''; //дата публикации
    public $pluses='';  //кол-во плюсов новости
    public $minuses=''; //кол-во минусов новости
    public $author_name='';  //имя автора поста
    public $cat_id='';  //ID категории, в которую мы сохраним пост из Блога
    //public $bot_name='';  //Имя бота, который добавит новость *NOT USED*
    public $tags = []; //Тэги
    public $additional='';  //Дополнительное поле, например, для картинки-значка новости или лиюбого другого дополнительного поля

    public $comments_counter=0; //Количество комментариев для новости (возможность публиковать только те посты, у которых есть комментарии)

}