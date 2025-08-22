<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->string('name')->nullable();
            $table->json('groups')->nullable(); // Array of group names
            $table->enum('source', ['manual', 'csv', 'newsletter'])->default('manual');
            $table->timestamp('added_at');
            $table->timestamps();

            $table->unique(['email', 'user_id']); // Prevent duplicate emails per user
            $table->index(['user_id', 'email']);
            $table->index(['user_id', 'source']);
            $table->index('added_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
