<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsYoutuberChannels extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_youtuber_channels', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('channel_id');
            $table->string('channel_caption');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_youtuber_channels');
    }
}
