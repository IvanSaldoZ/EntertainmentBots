<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsLog2 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_log', function($table)
        {
            $table->integer('bot_id');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_log', function($table)
        {
            $table->dropColumn('bot_id');
        });
    }
}
