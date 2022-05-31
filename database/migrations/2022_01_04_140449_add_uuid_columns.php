<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUuidColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        $posts = \App\Models\Post::get();

        $posts->each(function ($post) {
            $post->uuid = (string) \Illuminate\Support\Str::uuid();
            $post->save();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });


        Schema::table('issues', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        $issues = \App\Models\Issue::get();

        $issues->each(function ($issue) {
            $issue->uuid = (string) \Illuminate\Support\Str::uuid();
            $issue->save();
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
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
            $table->dropColumn('uuid');
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

    }
}
