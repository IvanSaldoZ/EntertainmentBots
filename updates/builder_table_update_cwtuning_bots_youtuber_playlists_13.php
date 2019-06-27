<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberPlaylists13 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_playlists', function($table)
        {
            $table->dropPrimary(['playlist_id','id']);
            $table->dropColumn('id');
            $table->primary(['playlist_id','channel_id']);
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_playlists', function($table)
        {
            $table->dropPrimary(['playlist_id','channel_id']);
            $table->integer('id');
            $table->primary(['playlist_id','id']);
        });
    }
}
