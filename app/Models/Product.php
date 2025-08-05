<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'intitule',
        'category_id',
        'reference',
        'lot',
        'date_fabrication',
        'date_peremption',
        'financement',
        'utilisateur_cible',
        'quantite', 'unite',
        'poids',
        'price',
        'image_id',
        'publish'
    ];
    public function image()
    {
        return $this->belongsTo(Image::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
