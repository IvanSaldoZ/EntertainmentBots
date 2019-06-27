<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberChannels2 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_channels', function($table)
        {
            $table->string('last_page_token');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_channels', function($table)
        {
            $table->dropColumn('last_page_token');
        });
    }
}
