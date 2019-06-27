<?php namespace Cwtuning\Bots\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateCwtuningBotsYoutuberVideoTitleFilter2 extends Migration
{
    public function up()
    {
        Schema::table('cwtuning_bots_youtuber_video_title_filter', function($table)
        {
            $table->boolean('is_on');
            $table->boolean('is_allowed')->nullable(false)->unsigned(false)->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('cwtuning_bots_youtuber_video_title_filter', function($table)
        {
            $table->dropColumn('is_on');
            $table->smallInteger('is_allowed')->nullable(false)->unsigned(false)->default(null)->change();
        });
    }
}
