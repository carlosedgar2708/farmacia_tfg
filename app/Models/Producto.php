<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'es_inyectable',
        'description',
    ];

    protected $casts = [
        'es_inyectable' => 'boolean',
    ];

    public function lotes()
    {
        return $this->hasMany(Lote::class);
    }
}
