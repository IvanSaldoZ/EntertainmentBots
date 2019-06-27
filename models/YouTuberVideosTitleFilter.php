<?php namespace Cwtuning\Bots\Models;

use Model;

/**
 * Model
 */
class YouTuberVideosTitleFilter extends Model
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
    public $table = 'cwtuning_bots_youtuber_video_title_filter';


    /*
     * Relations
     */
    public $belongsTo = [
        'channel' => [
            'Cwtuning\Bots\Models\YouTuberChannels',
            //'table' => 'cwtuning_bots_youtuber_channels',
            //'order' => 'channel_id',
            'key' => 'channel_id',
            //'foreignKey' => 'channel_id', //верно
            'otherKey' => 'channel_id', //верно
        ]
    ];
}