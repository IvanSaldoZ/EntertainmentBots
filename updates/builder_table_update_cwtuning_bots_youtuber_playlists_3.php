<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberPlaylists3 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_playlists', function($table)
        {
            $table->dropPrimary(['playlist_id']);
            $table->string('channel_id', 255)->nullable(false)->unsigned(false)->default(null)->change();
            $table->primary(['channel_id','playlist_id']);
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_playlists', function($table)
        {
            $table->dropPrimary(['channel_id','playlist_id']);
            $table->integer('channel_id')->nullable(false)->unsigned(false)->default(null)->change();
            $table->primary(['playlist_id']);
        });
    }
}
