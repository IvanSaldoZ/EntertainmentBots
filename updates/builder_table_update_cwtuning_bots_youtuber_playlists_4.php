<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberPlaylists4 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_playlists', function($table)
        {
            $table->dropPrimary(['channel_id','playlist_id']);
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_playlists', function($table)
        {
            $table->primary(['channel_id','playlist_id']);
        });
    }
}