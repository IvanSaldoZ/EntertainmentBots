<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsArticles extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_articles', function($table)
        {
            $table->text('source');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_articles', function($table)
        {
            $table->dropColumn('source');
        });
    }
}
