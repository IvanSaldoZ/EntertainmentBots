<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBots extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title');
            $table->boolean('is_on')->default(0);
            $table->dateTime('last_scanned');
            $table->string('parser_addr');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_');
    }
}
