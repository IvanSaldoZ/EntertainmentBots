<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsYoutuberComments extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_youtuber_comments', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('video_id');
            $table->text('comment');
            $table->string('author_display_name');
            $table->string('author_profile_image_url');
            $table->string('author_channel_url');
            $table->integer('like_count');
            $table->timestamp('published_at');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_youtuber_comments');
    }
}
