<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberChannels4 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_channels', function($table)
        {
            $table->dropColumn('category_id');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_channels', function($table)
        {
            $table->integer('category_id');
        });
    }
}
