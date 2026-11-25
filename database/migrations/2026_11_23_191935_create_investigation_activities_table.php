<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('case_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->foreignId('user_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->string('activity_type');
            $table->text('description');
            $table->dateTime('activity_date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_activities');
    }
};