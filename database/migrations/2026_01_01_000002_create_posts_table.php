<?php

use YasserElgammal\Green\Database\Migrations\Migration;
use YasserElgammal\Green\Database\Schema\Blueprint;
use YasserElgammal\Green\Database\Schema\Schema;

/**
 * Creates the `posts` table.
 * Demonstrates a foreign-key-style integer column and json column.
 */
class CreatePostsTable extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id', 'users', 'id', onDelete: 'CASCADE');
            $table->string('title');
            $table->string('status', 40)->default('draft');
            $table->string('image')->nullable();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
}
