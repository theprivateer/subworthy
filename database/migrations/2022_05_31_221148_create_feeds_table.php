<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feeds', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('protocol_less_url');
            $table->string('title');
            $table->string('link');
            $table->string('tld');
            $table->text('description')->nullable();
            $table->string('fetcher')->nullable();
            $table->string('formatter')->nullable();
            $table->string('next_check_at')->nullable();
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
        Schema::dropIfExists('feeds');
    }
}
