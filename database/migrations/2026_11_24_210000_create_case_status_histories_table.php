<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('case_id')
                  ->constrained('cases')
                  ->restrictOnDelete();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->dateTime('changed_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_status_histories');
    }
};
