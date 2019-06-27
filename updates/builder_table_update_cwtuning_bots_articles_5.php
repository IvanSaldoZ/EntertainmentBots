<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsArticles5 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_articles', function($table)
        {
            $table->integer('pluses');
            $table->integer('minuses');
            $table->integer('cat_id');
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_articles', function($table)
        {
            $table->dropColumn('pluses');
            $table->dropColumn('minuses');
            $table->dropColumn('cat_id');
        });
    }
}
