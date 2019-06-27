<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBots extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_', function($table)
        {
            $table->text('description');
            $table->increments('id')->unsigned(false)->change();
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_', function($table)
        {
            $table->dropColumn('description');
            $table->increments('id')->unsigned()->change();
        });
    }
}
