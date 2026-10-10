<?php

use YasserElgammal\Green\Database\Migrations\Migration;
use YasserElgammal\Green\Database\Schema\Blueprint;
use YasserElgammal\Green\Database\Schema\Schema;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
