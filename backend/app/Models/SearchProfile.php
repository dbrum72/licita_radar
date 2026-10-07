<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SearchProfile extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['keywords'=>'array', 'modalities'=>'array', 'states'=>'array', 'active'=>'boolean']; }
}
