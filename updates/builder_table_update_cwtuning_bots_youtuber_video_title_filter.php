<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberVideoTitleFilter extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_video_title_filter', function($table)
        {
            $table->string('channel_id', 255)->nullable(false)->unsigned(false)->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_video_title_filter', function($table)
        {
            $table->integer('channel_id')->nullable(false)->unsigned(false)->default(null)->change();
        });
    }
}
