<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->enum('test_type', ['subject', 'content'])->default('subject');
            $table->enum('status', ['draft', 'running', 'completed', 'cancelled'])->default('draft');
            $table->integer('test_percentage')->default(20);
            $table->timestamp('winner_selected_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('campaign_ab_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained('campaign_ab_tests')->cascadeOnDelete();
            $table->string('variant_key', 1); // A, B, C, etc.
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->integer('recipients_sent')->default(0);
            $table->integer('opens')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('open_rate', 5, 2)->default(0);
            $table->decimal('click_rate', 5, 2)->default(0);
            $table->boolean('is_winner')->default(false);
            $table->timestamps();

            $table->index(['ab_test_id', 'variant_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_ab_variants');
        Schema::dropIfExists('campaign_ab_tests');
    }
};
