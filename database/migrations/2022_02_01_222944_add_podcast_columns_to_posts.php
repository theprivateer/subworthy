<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPodcastColumnsToPosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('audio_length')->nullable()->after('fetched_raw');
            $table->string('audio_type')->nullable()->after('fetched_raw');
            $table->string('audio_url')->nullable()->after('fetched_raw');
            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['audio_url', 'audio_length', 'audio_type']);
        });
    }
}
