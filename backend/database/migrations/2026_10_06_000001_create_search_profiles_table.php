<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_profiles', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->json('keywords'); $t->json('modalities')->nullable();
            $t->json('states')->nullable(); $t->string('agency')->nullable();
            $t->decimal('min_value', 16, 2)->nullable(); $t->decimal('max_value', 16, 2)->nullable();
            $t->unsignedInteger('min_days')->default(3); $t->string('email')->nullable();
            $t->boolean('active')->default(true); $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_profiles');
    }
};