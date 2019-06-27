<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsArticlesTagsIds extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_articles_tags_ids', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('tag_id');
            $table->integer('article_id');
            $table->primary(['tag_id','article_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_articles_tags_ids');
    }
}
