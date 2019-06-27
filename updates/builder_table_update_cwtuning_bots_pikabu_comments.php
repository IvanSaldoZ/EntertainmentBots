<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsPikabuComments extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_pikabu_comments', function($table)
        {
            $table->string('link');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_pikabu_comments', function($table)
        {
            $table->dropColumn('link');
        });
    }
}
