<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement(
            "CREATE VIRTUAL TABLE IF NOT EXISTS complaints_fts USING fts5("
            . "complaint_number, complainant_name, incident_location, incident_description, "
            . "content='complaints', content_rowid='id')"
        );

        DB::statement(
            "CREATE VIRTUAL TABLE IF NOT EXISTS cases_fts USING fts5("
            . "case_number, title, description, content='cases', content_rowid='id')"
        );

        DB::statement(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS complaints_fts_ai AFTER INSERT ON complaints BEGIN
    INSERT INTO complaints_fts(rowid, complaint_number, complainant_name, incident_location, incident_description)
    VALUES (new.id, new.complaint_number, new.complainant_name, new.incident_location, new.incident_description);
END
SQL);

        DB::statement(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS complaints_fts_ad AFTER DELETE ON complaints BEGIN
    INSERT INTO complaints_fts(complaints_fts, rowid, complaint_number, complainant_name, incident_location, incident_description)
    VALUES ('delete', old.id, old.complaint_number, old.complainant_name, old.incident_location, old.incident_description);
END
SQL);

        DB::statement(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS complaints_fts_au AFTER UPDATE ON complaints BEGIN
    INSERT INTO complaints_fts(complaints_fts, rowid, complaint_number, complainant_name, incident_location, incident_description)
    VALUES ('delete', old.id, old.complaint_number, old.complainant_name, old.incident_location, old.incident_description);
    INSERT INTO complaints_fts(rowid, complaint_number, complainant_name, incident_location, incident_description)
    VALUES (new.id, new.complaint_number, new.complainant_name, new.incident_location, new.incident_description);
END
SQL);

        DB::statement(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS cases_fts_ai AFTER INSERT ON cases BEGIN
    INSERT INTO cases_fts(rowid, case_number, title, description)
    VALUES (new.id, new.case_number, new.title, new.description);
END
SQL);

        DB::statement(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS cases_fts_ad AFTER DELETE ON cases BEGIN
    INSERT INTO cases_fts(cases_fts, rowid, case_number, title, description)
    VALUES ('delete', old.id, old.case_number, old.title, old.description);
END
SQL);

        DB::statement(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS cases_fts_au AFTER UPDATE ON cases BEGIN
    INSERT INTO cases_fts(cases_fts, rowid, case_number, title, description)
    VALUES ('delete', old.id, old.case_number, old.title, old.description);
    INSERT INTO cases_fts(rowid, case_number, title, description)
    VALUES (new.id, new.case_number, new.title, new.description);
END
SQL);

        DB::statement("INSERT INTO complaints_fts(complaints_fts) VALUES('rebuild')");
        DB::statement("INSERT INTO cases_fts(cases_fts) VALUES('rebuild')");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        foreach (['complaints_fts_ai', 'complaints_fts_ad', 'complaints_fts_au',
            'cases_fts_ai', 'cases_fts_ad', 'cases_fts_au'] as $trigger) {
            DB::statement('DROP TRIGGER IF EXISTS ' . $trigger);
        }

        DB::statement('DROP TABLE IF EXISTS complaints_fts');
        DB::statement('DROP TABLE IF EXISTS cases_fts');
    }
};
