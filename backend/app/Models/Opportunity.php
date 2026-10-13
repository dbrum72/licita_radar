<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['source' => 'array', 'published_at' => 'datetime', 'deadline' => 'datetime', 'bookmarked' => 'boolean', 'estimated_value' => 'decimal:2'];
    }

    public static function openDeadlineBoundary(): Carbon
    {
        return now('America/Sao_Paulo')->startOfDay();
    }

    public function hasOpenDeadline(): bool
    {
        return $this->deadline !== null
            && $this->deadline->copy()->timezone('America/Sao_Paulo')->gte(self::openDeadlineBoundary());
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function requirements()
    {
        return $this->hasMany(Requirement::class);
    }

    public function dismissal()
    {
        return $this->hasOne(OpportunityDismissal::class);
    }

    public function getPncpUrlAttribute(): string
    {
        return "https://pncp.gov.br/app/editais/{$this->cnpj}/{$this->year}/{$this->sequence}";
    }

    protected $appends = ['pncp_url'];
}
