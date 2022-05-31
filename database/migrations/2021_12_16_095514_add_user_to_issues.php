<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserToIssues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });

        $issues = \App\Models\Issue::with('daily')->get();

        foreach($issues as $issue)
        {
            $issue->update(['user_id' => $issue->daily->user_id]);
        }

        Schema::table('issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('daily_id');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('issues', function (Blueprint $table) {
            //
        });
    }
}
