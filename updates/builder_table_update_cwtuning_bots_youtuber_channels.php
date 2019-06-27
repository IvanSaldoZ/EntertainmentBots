<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberChannels extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_channels', function($table)
        {
            $table->dateTime('last_scanned');
            $table->boolean('is_on');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_channels', function($table)
        {
            $table->dropColumn('last_scanned');
            $table->dropColumn('is_on');
        });
    }
}
