<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUuidToFeeds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->string('protocol_less_url')->nullable()->after('url');
        });

        $feeds = \App\Models\Feed::get();

        $feeds->each(function ($feed) {
            $feed->uuid = (string) \Illuminate\Support\Str::uuid();

            $uri = \League\Uri\Uri::createFromString($feed->url);

            $scheme = $uri->getScheme();

            $feed->protocol_less_url = str_replace($scheme . '://', '', $feed->url);

            $feed->save();
        });

        Schema::table('feeds', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->string('protocol_less_url')->nullable(false)->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'url']);
        });
    }
}
