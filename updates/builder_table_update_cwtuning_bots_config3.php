<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsConfig3 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_config', function($table)
        {
            $table->smallInteger('last_scanned_bot_id')->nullable(false)->unsigned(false)->default(null)->change();
            $table->primary(['id']);
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_config', function($table)
        {
            $table->integer('last_scanned_bot_id')->nullable(false)->unsigned(false)->default(null)->change();
            $table->primary(['last_scanned_bot_id']);
        });
    }
}