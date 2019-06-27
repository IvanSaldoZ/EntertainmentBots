<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsBotsCategories extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_bots_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('bot_id');
            $table->integer('category_id');
            $table->primary(['bot_id','category_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_bots_categories');
    }
}
