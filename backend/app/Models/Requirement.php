<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Requirement extends Model
{
    protected $guarded = ['id'];
    public function evidence() { return $this->belongsTo(CompanyDocument::class, 'evidence_document_id'); }
}
