<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberVideolist extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_videolist', function($table)
        {
            $table->text('description')->nullable(false)->unsigned(false)->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_videolist', function($table)
        {
            $table->string('description', 255)->nullable(false)->unsigned(false)->default(null)->change();
        });
    }
}
