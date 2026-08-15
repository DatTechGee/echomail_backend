<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('trigger_type', ['subscriber_joins', 'subscriber_tag', 'date_based', 'manual'])->default('manual');
            $table->json('trigger_config')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');
            $table->integer('total_enrolled')->default(0);
            $table->integer('total_completed')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('automation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->integer('step_order')->default(0);
            $table->enum('step_type', ['wait', 'send_email', 'condition', 'tag', 'end'])->default('wait');
            $table->json('step_config')->nullable();
            $table->timestamps();

            $table->index(['automation_id', 'step_order']);
        });

        Schema::create('automation_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->enum('status', ['active', 'completed', 'exited'])->default('active');
            $table->integer('current_step')->default(0);
            $table->timestamp('next_action_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['automation_id', 'status']);
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_enrollments');
        Schema::dropIfExists('automation_steps');
        Schema::dropIfExists('automations');
    }
};
