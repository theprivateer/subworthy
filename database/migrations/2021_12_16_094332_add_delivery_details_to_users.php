<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryDetailsToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('last_delivered_at')->nullable()->after('timezone');
            $table->string('days_of_week')->default('1234567')->after('timezone');
            $table->string('delivery_time')->default('0000')->after('timezone');
        });

        $users = \App\Models\User::get();

        foreach($users as $user)
        {
            $daily = \App\Models\Daily::find($user->default_daily_id);

            $user->update([
               'delivery_time' => $daily->delivery_time,
               'days_of_week' => $daily->days_of_week,
               'last_delivered_at' => $daily->last_delivered_at,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('default_daily_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['delivery_time', 'days_of_week', 'last_delivered_at']);

            $table->unsignedBigInteger('default_daily_id')->nullable();
        });
    }
}
