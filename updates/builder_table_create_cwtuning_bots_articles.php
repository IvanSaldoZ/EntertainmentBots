<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsArticles extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_articles', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id');
            $table->string('title');
            $table->string('slug');
            $table->text('content');
            $table->timestamp('published_at');
            $table->boolean('is_publeshed');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_articles');
    }
}
