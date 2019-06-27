<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsYoutuberVideoTitleFilter extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_youtuber_video_title_filter', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('channel_id');
            $table->string('filtered_words');
            $table->smallInteger('is_allowed');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_youtuber_video_title_filter');
    }
}
