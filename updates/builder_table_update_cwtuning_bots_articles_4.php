<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsArticles4 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_articles', function($table)
        {
            $table->integer('post_id')->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_articles', function($table)
        {
            $table->dropColumn('post_id');
        });
    }
}
