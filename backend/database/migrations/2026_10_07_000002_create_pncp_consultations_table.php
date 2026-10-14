<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pncp_consultations', function (Blueprint $table) {
            $table->id();
            $table->string('pncp_id')->unique();
            $table->timestamp('first_seen_at');
        });
        // Independent from opportunities so deletion cannot allow reimport.
        DB::table('opportunities')->select('id', 'pncp_id', 'created_at')->chunkById(250, function ($items) {
            DB::table('pncp_consultations')->insertOrIgnore($items->map(fn ($item) => [
                'pncp_id' => $item->pncp_id, 'first_seen_at' => $item->created_at ?? now(),
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pncp_consultations');
    }
};
