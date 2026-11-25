<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_histories', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_histories');
    }
};