<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsLog extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_log', function($table)
        {
            $table->text('result');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_log', function($table)
        {
            $table->dropColumn('result');
        });
    }
}
