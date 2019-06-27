<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberVideolist3 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_videolist', function($table)
        {
            $table->integer('pluses');
            $table->integer('minuses');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_videolist', function($table)
        {
            $table->dropColumn('pluses');
            $table->dropColumn('minuses');
        });
    }
}
