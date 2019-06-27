<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsPikabuStories extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_pikabu_stories', function($table)
        {
            $table->string('link');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_pikabu_stories', function($table)
        {
            $table->dropColumn('link');
        });
    }
}
