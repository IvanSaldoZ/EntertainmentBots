<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsLogBots extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_log_bots', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('log_id');
            $table->integer('bots_id');
            $table->primary(['log_id','bots_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_log_bots');
    }
}
