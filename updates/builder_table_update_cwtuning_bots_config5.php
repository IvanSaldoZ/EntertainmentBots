<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsConfig5 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_config', function($table)
        {
            $table->integer('id');
            $table->primary(['id']);
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_config', function($table)
        {
            $table->dropColumn('id');
            $table->dropPrimary(['id']);
        });
    }
}