<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBots5 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_', function($table)
        {
            $table->boolean('is_public')->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_', function($table)
        {
            $table->dropColumn('is_public');
        });
    }
}
