<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberVideolist4 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_videolist', function($table)
        {
            $table->integer('post_id');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_videolist', function($table)
        {
            $table->dropColumn('post_id');
        });
    }
}
