<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsYoutuberPlaylists extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_youtuber_playlists', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('channel_id');
            $table->string('playlist_id');
            $table->primary(['channel_id','playlist_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_youtuber_playlists');
    }
}
