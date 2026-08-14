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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('sent_at');
            $table->string('frequency')->nullable()->after('scheduled_at');
            $table->timestamp('next_run_at')->nullable()->after('frequency');
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed'])
                ->default('draft')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'frequency', 'next_run_at']);
            $table->enum('status', ['draft', 'sent', 'failed'])->default('draft')->change();
        });
    }
};
