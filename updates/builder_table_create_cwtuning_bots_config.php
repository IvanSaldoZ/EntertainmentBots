<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsConfig extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_config', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('id');
            $table->integer('last_scanned_bot_id');
            $table->primary(['id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_config');
    }
}
