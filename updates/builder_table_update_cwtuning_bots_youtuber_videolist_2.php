<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberVideolist2 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_videolist', function($table)
        {
            $table->boolean('published')->default(0);
            $table->integer('comments_counter')->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_videolist', function($table)
        {
            $table->dropColumn('published');
            $table->dropColumn('comments_counter');
        });
    }
}
