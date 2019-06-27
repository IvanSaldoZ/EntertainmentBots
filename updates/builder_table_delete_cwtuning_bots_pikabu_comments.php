<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteCwtuningBotsPikabuComments extends Migration
{
    public function up()
    {
        Schema::dropIfExists('cwtuning_bots_pikabu_comments');
    }
    
    public function down()
    {
        Schema::create('cwtuning_bots_pikabu_comments', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('story_id', 255);
            $table->text('comment');
            $table->string('author_display_name', 255);
            $table->string('author_profile_image_url', 255);
            $table->string('author_profile_url', 255);
            $table->integer('like_count');
            $table->timestamp('published_at')->default('0000-00-00 00:00:00');
            $table->string('unique_id', 255);
            $table->string('link', 255);
        });
    }
}
