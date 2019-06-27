<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsArticles8 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_articles', function($table)
        {
            $table->string('bot_name');
            $table->dropColumn('bot_id');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_articles', function($table)
        {
            $table->dropColumn('bot_name');
            $table->integer('bot_id');
        });
    }
}
