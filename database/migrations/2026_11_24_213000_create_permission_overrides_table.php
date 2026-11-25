<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('permission');
            $table->boolean('allowed');
            $table->timestamps();

            $table->unique(['role', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_overrides');
    }
};
