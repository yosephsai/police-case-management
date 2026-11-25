<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_people', function (Blueprint $table) {
            $table->id();

            $table->foreignId('case_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->string('full_name');
            $table->string('role');
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_people');
    }
};