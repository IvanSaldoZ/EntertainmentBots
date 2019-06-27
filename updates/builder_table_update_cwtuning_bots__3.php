<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBots3 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_', function($table)
        {
            $table->smallInteger('minutes_between')->default(1440)->change();
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_', function($table)
        {
            $table->smallInteger('minutes_between')->default(3600)->change();
        });
    }
}
