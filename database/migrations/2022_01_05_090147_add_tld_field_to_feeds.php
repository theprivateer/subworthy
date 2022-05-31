<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use League\Uri\Uri;

class AddTldFieldToFeeds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->string('tld')->nullable()->after('link');
        });

        $feeds = \App\Models\Feed::get();

        foreach($feeds as $feed)
        {
            $uri = Uri::createFromString($feed->link ?? $feed->url);

            $feed->tld = $uri->getScheme() . '://' . $uri->getHost();
            $feed->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->dropColumn('tld');
        });
    }
}
