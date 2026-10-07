<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $t) {
            $t->id(); $t->string('status'); $t->unsignedInteger('imported')->default(0);
            $t->unsignedInteger('matched')->default(0); $t->text('error')->nullable();
            $t->timestamp('started_at'); $t->timestamp('finished_at')->nullable(); $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};