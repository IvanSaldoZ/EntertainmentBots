<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberComments extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_comments', function($table)
        {
            $table->string('unique_id');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_comments', function($table)
        {
            $table->dropColumn('unique_id');
        });
    }
}
