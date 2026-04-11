<?php

use YasserElgammal\Green\Database\Migrations\Migration;
use YasserElgammal\Green\Database\Schema\Blueprint;
use YasserElgammal\Green\Database\Schema\Schema;

class CreateCommentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id', 'posts', 'id', onDelete: 'CASCADE');
            $table->foreignId('user_id', 'users', 'id', onDelete: 'CASCADE');
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
}
