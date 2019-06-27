<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsBotsArticles extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_bots_articles', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('bot_id');
            $table->integer('article_id');
            $table->primary(['bot_id','article_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_bots_articles');
    }
}
