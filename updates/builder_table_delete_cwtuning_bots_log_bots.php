<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteCwtuningBotsLogBots extends Migration
{
    public function up()
    {
        Schema::dropIfExists('cwtuning_bots_log_bots');
    }
    
    public function down()
    {
        Schema::create('cwtuning_bots_log_bots', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('log_id');
            $table->integer('bots_id');
            $table->primary(['log_id','bots_id']);
        });
    }
}
