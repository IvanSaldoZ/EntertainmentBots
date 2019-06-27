<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsPikabuStories extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_pikabu_stories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('story_id');
            $table->string('title');
            $table->text('content');
            $table->timestamp('published_at');
            $table->boolean('is_published');
            $table->smallInteger('cat_id');
            $table->integer('pluses');
            $table->integer('minuses');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_pikabu_stories');
    }
}
