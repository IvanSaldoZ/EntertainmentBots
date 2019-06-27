<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteCwtuningBotsArticlesTags extends Migration
{
    public function up()
    {
        Schema::dropIfExists('cwtuning_bots_articles_tags');
    }
    
    public function down()
    {
        Schema::create('cwtuning_bots_articles_tags', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
}
