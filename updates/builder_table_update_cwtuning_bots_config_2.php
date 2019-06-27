<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsConfig2 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_config', function($table)
        {
            $table->primary(['last_scanned_bot_id']);
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_config', function($table)
        {
            $table->dropPrimary(['last_scanned_bot_id']);
        });
    }
}
