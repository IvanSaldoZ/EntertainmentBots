<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBots4 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_', function($table)
        {
            $table->smallInteger('hours_begin')->default(0);
            $table->smallInteger('hours_end')->default(24);
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_', function($table)
        {
            $table->dropColumn('hours_begin');
            $table->dropColumn('hours_end');
        });
    }
}
