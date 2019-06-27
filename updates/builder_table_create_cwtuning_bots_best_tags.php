<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsBestTags extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_best_tags', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('tag_name');
            $table->integer('post_count');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_best_tags');
    }
}
