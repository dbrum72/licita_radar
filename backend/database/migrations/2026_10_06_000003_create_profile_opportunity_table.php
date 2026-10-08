<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_opportunity', function (Blueprint $t) {
            $t->id();
            $t->foreignId('search_profile_id')->constrained()->cascadeOnDelete();
            $t->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $t->timestamp('notified_at')->nullable();
            $t->timestamps();
            $t->unique(['search_profile_id', 'opportunity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_opportunity');
    }
};
