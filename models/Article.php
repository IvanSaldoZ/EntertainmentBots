<?php namespace Cwtuning\Bots\Models;

use Model;

/**
 * Model
 */
class Article extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sluggable;
    
    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cwtuning_bots_articles';


    /**
     * @var array Generate slugs for these attributes.
     */
    protected $slugs = ['slug' => 'title'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'title',
        'content',
        'published_at',
        'is_published',
        'created_at',
        'updated_at',
        'user_id',
        'post_id',
        'pluses',
        'minuses',
        'cat_id',
        'link_id',
        'bot_name',
        'source',
    ];


//    public $belongsToMany = [
//        'bot' => [
//            'Cwtuning\Bots\Models\Bot',
//            'table' => 'cwtuning_bots_bots_articles',
//            'order' => 'title'
//        ]
//    ];


    /**
     * One article may be added only by ONE bot
     */
    public $hasOne = [
        'bot' => ['Cwtuning\Bots\Models\Bot', 'key'=>'id', 'otherKey'=>'bot_id'],
        'post' => ['Rainlab\Blog\Models\Post', 'key'=>'id', 'otherKey'=>'post_id'],
    ];

}