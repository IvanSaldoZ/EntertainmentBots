<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsConfig4 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_config', function($table)
        {
            $table->dropPrimary(['id']);
            $table->dropColumn('id');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_config', function($table)
        {
            $table->integer('id');
            $table->primary(['id']);
        });
    }
}