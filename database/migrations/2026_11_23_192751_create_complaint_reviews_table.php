<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->foreignId('user_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->string('decision');
            $table->text('reason')->nullable();
            $table->dateTime('reviewed_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_reviews');
    }
};