<?php namespace Cwtuning\Bots\Models;

use Model;


/**
 * Model
 */
class Bot extends Model
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
    public $table = 'cwtuning_bots_';


    /*
     * Relations
     */
    public $belongsToMany = [
        'categories' => [
            'RainLab\Blog\Models\Category',
            'table' => 'cwtuning_bots_bots_categories',
            'order' => 'name',
        ]
    ];

    protected $fillable = ['last_scanned_id'];
}