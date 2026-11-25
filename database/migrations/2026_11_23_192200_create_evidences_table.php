<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('case_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->foreignId('user_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->string('evidence_number')->unique();
            $table->string('evidence_type');
            $table->text('description');
            $table->string('storage_location')->nullable();
            $table->date('collected_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidences');
    }
};