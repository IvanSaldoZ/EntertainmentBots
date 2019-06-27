<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteCwtuningBotsPikabuStories extends Migration
{
    public function up()
    {
        Schema::dropIfExists('cwtuning_bots_pikabu_stories');
    }
    
    public function down()
    {
        Schema::create('cwtuning_bots_pikabu_stories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('story_id');
            $table->string('title', 255);
            $table->text('content');
            $table->timestamp('published_at');
            $table->boolean('is_published');
            $table->smallInteger('cat_id');
            $table->integer('pluses');
            $table->integer('minuses');
            $table->string('link', 255);
        });
    }
}
