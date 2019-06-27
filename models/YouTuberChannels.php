<?php namespace Cwtuning\Bots\Models;

use Model;

/**
 * Model
 */
class YouTuberChannels extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cwtuning_bots_youtuber_channels';


    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'last_page_token',
        'last_scanned',
    ];


    /*
     * Relations
     */
    public $belongsToMany = [
        'categories' => [
            'RainLab\Blog\Models\Category',
            'table' => 'cwtuning_bots_youtuber_channels_categories',
            'order' => 'name',
            'key' => 'channel_id',
            'otherKey' => 'category_id',
        ]
    ];






}