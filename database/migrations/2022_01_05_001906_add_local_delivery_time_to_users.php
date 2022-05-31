<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocalDeliveryTimeToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('delivery_time_local')->default('0000')->after('delivery_time');
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Update values
        $users = \App\Models\User::get();

        foreach($users as $user)
        {
            $user->uuid = (string) \Illuminate\Support\Str::uuid();

            $time = \Carbon\Carbon::createFromFormat('Hi', $user->delivery_time);
            $time->setTimezone($user->timezone);

            $user->delivery_time_local = $time->format('Hi');

            $user->save();
        }

        Schema::table('users', function (Blueprint $table) {
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['delivery_time_local', 'uuid']);
        });
    }
}
