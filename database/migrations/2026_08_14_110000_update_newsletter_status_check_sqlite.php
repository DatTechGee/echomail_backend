<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::unprepared("
            PRAGMA foreign_keys = OFF;

            BEGIN TRANSACTION;

            CREATE TABLE newsletter_subscribers_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL,
                email TEXT NOT NULL,
                name TEXT,
                phone TEXT,
                source TEXT NOT NULL DEFAULT 'website',
                status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('pending','active','unsubscribed')),
                subscribed_at DATETIME NOT NULL,
                unsubscribed_at DATETIME,
                unsubscribe_token TEXT NOT NULL,
                verify_token TEXT,
                verified_at DATETIME,
                preferences TEXT,
                created_at DATETIME,
                updated_at DATETIME
            );

            INSERT INTO newsletter_subscribers_new (id, uuid, email, name, phone, source, status, subscribed_at, unsubscribed_at, unsubscribe_token, verify_token, verified_at, preferences, created_at, updated_at)
                SELECT id, uuid, email, name, phone, source, status, subscribed_at, unsubscribed_at, unsubscribe_token, verify_token, verified_at, preferences, created_at, updated_at
                FROM newsletter_subscribers;

            DROP TABLE newsletter_subscribers;

            ALTER TABLE newsletter_subscribers_new RENAME TO newsletter_subscribers;

            CREATE UNIQUE INDEX newsletter_subscribers_uuid_unique ON newsletter_subscribers (uuid);
            CREATE UNIQUE INDEX newsletter_subscribers_email_unique ON newsletter_subscribers (email);
            CREATE UNIQUE INDEX newsletter_subscribers_unsubscribe_token_unique ON newsletter_subscribers (unsubscribe_token);
            CREATE UNIQUE INDEX newsletter_subscribers_verify_token_unique ON newsletter_subscribers (verify_token);
            CREATE INDEX newsletter_subscribers_email_status_index ON newsletter_subscribers (email, status);
            CREATE INDEX newsletter_subscribers_status_index ON newsletter_subscribers (status);
            CREATE INDEX newsletter_subscribers_source_index ON newsletter_subscribers (source);
            CREATE INDEX newsletter_subscribers_subscribed_at_index ON newsletter_subscribers (subscribed_at);

            COMMIT;

            PRAGMA foreign_keys = ON;
        ");
    }

    public function down(): void
    {
        // Recreated via the add_optin migration path; no-op on SQLite.
    }
};
