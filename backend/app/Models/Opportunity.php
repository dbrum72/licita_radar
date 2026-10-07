<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['source'=>'array', 'published_at'=>'datetime', 'deadline'=>'datetime', 'bookmarked'=>'boolean', 'estimated_value'=>'decimal:2']; }
    public function documents() { return $this->hasMany(Document::class); }
    public function requirements() { return $this->hasMany(Requirement::class); }
    public function dismissal() { return $this->hasOne(OpportunityDismissal::class); }
    public function getPncpUrlAttribute(): string { return "https://pncp.gov.br/app/editais/{$this->cnpj}/{$this->year}/{$this->sequence}"; }
    protected $appends = ['pncp_url'];
}
