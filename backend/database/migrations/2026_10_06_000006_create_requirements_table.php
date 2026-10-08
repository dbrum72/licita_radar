<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $t->foreignId('source_document_id')->constrained('documents')->cascadeOnDelete();
            $t->unsignedInteger('source_page');
            $t->text('description');
            $t->string('status')->default('pending');
            $t->foreignId('evidence_document_id')->nullable()->constrained('company_documents')->nullOnDelete();
            $t->unsignedInteger('evidence_page')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirements');
    }
};
