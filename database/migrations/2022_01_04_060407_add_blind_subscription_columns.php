<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBlindSubscriptionColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->string('notify_on_approval')->nullable()->after('title');
            $table->boolean('approved')->default(true)->after('title');
            $table->string('source')->default('direct')->after('title');
        });

        $subscriptions = \App\Models\Subscription::get();

        $subscriptions->each(function ($subscription) {
           $subscription->uuid = (string) \Illuminate\Support\Str::uuid();
           $subscription->save();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'approved', 'source', 'notify_on_approval']);
        });
    }
}
