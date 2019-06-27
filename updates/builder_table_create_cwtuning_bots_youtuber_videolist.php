<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsYoutuberVideolist extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_youtuber_videolist', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('video_id');
            $table->string('title');
            $table->timestamp('published_at');
            $table->string('channel_id');
            $table->string('description');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_youtuber_videolist');
    }
}
