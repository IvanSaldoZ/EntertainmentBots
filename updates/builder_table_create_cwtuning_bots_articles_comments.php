<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsArticlesComments extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_articles_comments', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('article_id');
            $table->text('comment');
            $table->string('author_display_name');
            $table->string('author_profile_image_url');
            $table->string('author_profile_url');
            $table->integer('like_count');
            $table->timestamp('published_at');
            $table->string('unique_id');
            $table->text('link');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_articles_comments');
    }
}
