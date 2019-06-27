<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsBlogtitleFilter extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_blogtitle_filter', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('filtered_word');
            $table->boolean('is_on');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_blogtitle_filter');
    }
}
