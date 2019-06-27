<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsArticles9 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_articles', function($table)
        {
            $table->string('source', 512)->nullable(false)->unsigned(false)->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_articles', function($table)
        {
            $table->text('source')->nullable(false)->unsigned(false)->default(null)->change();
        });
    }
}
