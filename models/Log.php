<?php namespace Cwtuning\Bots\Models;

use Model;

/**
 * Model
 */
class Log extends Model
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
    public $table = 'cwtuning_bots_log';




    /**
     * One entity may be added only by ONE BOT
     */
    public $hasOne = [
        'bot' => ['Cwtuning\Bots\Models\Bot', 'key'=>'id', 'otherKey'=>'bot_id'],
    ];
}