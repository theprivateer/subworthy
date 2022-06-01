<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_id')->constrained()->cascadeOnDelete();
            $table->string('source_id');
            $table->string('url');
            $table->string('title')->nullable();
            $table->text('preview')->nullable();
            $table->longText('raw')->nullable();
            $table->longText('fetched_raw')->nullable();
            $table->text('audio_url')->nullable();
            $table->dateTime('published_at');
            $table->dateTime('modified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
