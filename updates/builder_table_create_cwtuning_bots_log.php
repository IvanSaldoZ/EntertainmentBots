<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsLog extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_log', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->dateTime('datetime')->nullable();
            $table->smallInteger('action_id');
            $table->text('parser_address')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_log');
    }
}
