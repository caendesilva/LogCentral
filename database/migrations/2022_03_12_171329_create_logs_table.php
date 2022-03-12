<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Team;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class); // The user who owns the team
            $table->foreignIdFor(Team::class); // The team the Log is associated with
            $table->enum('level', [
                'EMERGENCY',
                'ALERT',
                'CRITICAL',
                'ERROR',
                'WARNING',
                'NOTICE',
                'INFO',
                'DEBUG'
            ]); // Uses log levels from RFC 5424, @see \Psr\Log\LogLevel::class
            $table->integer('timestamp'); // A 4-byte/32bit integer since Unix Time is 32bit
            $table->string('label', 64)->nullable();
            $table->string('message');
            $table->json('context')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
};
