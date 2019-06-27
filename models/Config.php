<?php namespace Cwtuning\Bots\Models;

use Model;

/**
 * Model
 */
class Config extends Model
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
    public $table = 'cwtuning_bots_config';


    protected $fillable = ['last_scanned_bot_id'];
}