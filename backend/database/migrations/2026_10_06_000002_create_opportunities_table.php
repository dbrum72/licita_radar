<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $t) {
            $t->id(); $t->string('pncp_id')->unique(); $t->string('cnpj', 20); $t->unsignedInteger('year');
            $t->unsignedInteger('sequence'); $t->text('object'); $t->string('agency');
            $t->string('state', 2)->nullable(); $t->string('city')->nullable();
            $t->unsignedInteger('modality_id'); $t->string('modality'); $t->decimal('estimated_value', 16, 2)->nullable();
            $t->timestamp('published_at')->nullable(); $t->timestamp('deadline')->nullable()->index();
            $t->string('scope_status')->default('review'); $t->text('scope_reason');
            $t->string('attachments_status')->default('pending'); $t->text('attachments_error')->nullable();
            $t->boolean('bookmarked')->default(false); $t->json('source'); $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};