<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('case_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->foreignId('user_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->string('role')->default('investigator');
            $table->date('assigned_at');

            $table->timestamps();

            $table->unique(['case_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_assignments');
    }
};