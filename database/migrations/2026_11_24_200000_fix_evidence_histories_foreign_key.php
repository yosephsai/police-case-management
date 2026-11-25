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
        Schema::create('evidence_histories_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')
                ->constrained('evidences')
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('action');
            $table->text('notes')->nullable();
            $table->dateTime('handled_at');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::statement(
            'INSERT INTO evidence_histories_new (evidence_id, user_id, action, notes, handled_at, created_at, updated_at)
             SELECT evidence_id, user_id, action, notes, handled_at, created_at, updated_at FROM evidence_histories'
        );

        Schema::drop('evidence_histories');
        Schema::rename('evidence_histories_new', 'evidence_histories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('evidence_histories_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')
                ->constrained('evidence')
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('action');
            $table->text('notes')->nullable();
            $table->dateTime('handled_at');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::statement(
            'INSERT INTO evidence_histories_old (evidence_id, user_id, action, notes, handled_at, created_at, updated_at)
             SELECT evidence_id, user_id, action, notes, handled_at, created_at, updated_at FROM evidence_histories'
        );

        Schema::drop('evidence_histories');
        Schema::rename('evidence_histories_old', 'evidence_histories');
    }
};
