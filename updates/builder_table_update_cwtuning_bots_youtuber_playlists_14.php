<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberPlaylists14 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_playlists', function($table)
        {
            $table->dropPrimary(['playlist_id','channel_id']);
            $table->integer('id');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_playlists', function($table)
        {
            $table->dropColumn('id');
            $table->primary(['playlist_id','channel_id']);
        });
    }
}
