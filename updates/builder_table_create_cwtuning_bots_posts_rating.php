<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCwtuningBotsPostsRating extends Migration
{
    public function up()
    {
        Schema::create('cwtuning_bots_posts_rating', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('post_id');
            $table->integer('rating');
            $table->primary(['post_id','rating']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('cwtuning_bots_posts_rating');
    }
}
