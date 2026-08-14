<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE newsletter_subscribers MODIFY status ENUM('pending','active','unsubscribed') NOT NULL DEFAULT 'active'");
        }

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->string('verify_token')->nullable()->unique()->after('unsubscribe_token');
            $table->timestamp('verified_at')->nullable()->after('verify_token');
            $table->json('preferences')->nullable()->after('verified_at');
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->timestamp('bounced_at')->nullable()->after('error_message');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->string('entity_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('url');
            $table->json('events')->nullable();
            $table->string('secret')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('webhook_id')->index();
            $table->string('event');
            $table->json('payload')->nullable();
            $table->integer('status_code')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('audit_logs');

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropColumn('bounced_at');
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn(['verify_token', 'verified_at', 'preferences']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE newsletter_subscribers MODIFY status ENUM('active','unsubscribed') NOT NULL DEFAULT 'active'");
        }
    }
};
