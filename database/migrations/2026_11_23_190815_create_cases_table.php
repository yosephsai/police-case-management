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
        Schema::create('cases', function (Blueprint $table) {
    $table->id();

    $table->foreignId('complaint_id')
          ->unique()
          ->constrained()
          ->restrictOnDelete();

    $table->string('case_number')->unique();
    $table->string('title');
    $table->text('description')->nullable();
    $table->string('status')->default('open');
    $table->date('opened_at')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
