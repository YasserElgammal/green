<?php

use YasserElgammal\Green\Database\Migrations\Migration;
use YasserElgammal\Green\Database\Schema\Blueprint;
use YasserElgammal\Green\Database\Schema\Schema;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
