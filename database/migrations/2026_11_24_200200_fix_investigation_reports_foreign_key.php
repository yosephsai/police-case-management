<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_reports_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')
                ->constrained('cases')
                ->restrictOnDelete();
            $table->foreignId('submitted_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('report_summary')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->string('review_status')->default('pending');
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::statement(
            'INSERT INTO investigation_reports_new (case_id, submitted_by, report_summary, submitted_at, review_status, review_notes, reviewed_by, reviewed_at, created_at, updated_at)
             SELECT case_id, submitted_by, report_summary, submitted_at, review_status, review_notes, reviewed_by, reviewed_at, created_at, updated_at FROM investigation_reports'
        );

        Schema::drop('investigation_reports');
        Schema::rename('investigation_reports_new', 'investigation_reports');
    }

    public function down(): void
    {
        Schema::create('investigation_reports_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')
                ->constrained('cases')
                ->restrictOnDelete();
            $table->foreignId('submitted_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('report_summary')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->string('review_status')->default('pending');
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::statement(
            'INSERT INTO investigation_reports_old (case_id, submitted_by, report_summary, submitted_at, review_status, review_notes, reviewed_by, reviewed_at, created_at, updated_at)
             SELECT case_id, submitted_by, report_summary, submitted_at, review_status, review_notes, reviewed_by, reviewed_at, created_at, updated_at FROM investigation_reports'
        );

        Schema::drop('investigation_reports');
        Schema::rename('investigation_reports_old', 'investigation_reports');
    }
};
