<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Document extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['path'];
    protected function casts(): array { return ['pages'=>'array', 'expires_at'=>'date']; }
}
