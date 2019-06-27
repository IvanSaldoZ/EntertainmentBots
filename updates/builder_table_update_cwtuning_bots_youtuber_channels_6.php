<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberChannels6 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_channels', function($table)
        {
            $table->renameColumn('asc', 'desc');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_channels', function($table)
        {
            $table->renameColumn('desc', 'asc');
        });
    }
}
