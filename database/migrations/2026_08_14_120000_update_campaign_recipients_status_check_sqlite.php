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

            CREATE TABLE campaign_recipients_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                campaign_id INTEGER NOT NULL,
                email TEXT NOT NULL,
                token TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','sent','failed','bounced')),
                opened_at DATETIME,
                clicked_at DATETIME,
                error_message TEXT,
                bounced_at DATETIME,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE CASCADE
            );

            INSERT INTO campaign_recipients_new (id, campaign_id, email, token, status, opened_at, clicked_at, error_message, bounced_at, created_at, updated_at)
                SELECT id, campaign_id, email, token, status, opened_at, clicked_at, error_message, bounced_at, created_at, updated_at
                FROM campaign_recipients;

            DROP TABLE campaign_recipients;

            ALTER TABLE campaign_recipients_new RENAME TO campaign_recipients;

            CREATE UNIQUE INDEX campaign_recipients_token_unique ON campaign_recipients (token);
            CREATE INDEX campaign_recipients_campaign_id_status_index ON campaign_recipients (campaign_id, status);
            CREATE INDEX campaign_recipients_campaign_id_email_index ON campaign_recipients (campaign_id, email);

            COMMIT;

            PRAGMA foreign_keys = ON;
        ");
    }

    public function down(): void
    {
        // Recreated via the add_optin migration path; no-op on SQLite.
    }
};
