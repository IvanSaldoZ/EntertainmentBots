<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsBestCommentsDaily extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_best_comments_daily', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('article_id');
            $table->integer('post_id');
            $table->string('post_slug');
            $table->text('comment');
            $table->string('author_display_name');
            $table->string('author_profile_image_url');
            $table->string('author_profile_url');
            $table->integer('like_count');
            $table->timestamp('published_at');
            $table->string('unique_id');
            $table->text('link');
            $table->date('best_date');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_best_comments_daily');
    }
}
