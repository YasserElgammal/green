<?php

use YasserElgammal\Green\Database\Migrations\Migration;
use YasserElgammal\Green\Database\Schema\Blueprint;
use YasserElgammal\Green\Database\Schema\Schema;

class CreateErrorLogsTable extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->string('id');
            $table->integer('level');
            $table->string('type', 255)->nullable();
            $table->text('message')->nullable();
            $table->string('file', 500)->nullable();
            $table->integer('line')->nullable();
            $table->text('stack_trace')->nullable();
            $table->text('context')->nullable(); // Saved as JSON encoded string
            $table->string('fingerprint', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
}
