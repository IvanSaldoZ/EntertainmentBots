<?php namespace Cwtuning\Bots\Models;

use Model;

/**
 * Model
 */
class BlogTitleFilter extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cwtuning_bots_blogtitle_filter';
}