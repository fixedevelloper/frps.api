<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['intitule', 'parent_id','image_id'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    public function image()
    {
        return $this->belongsTo(Image::class);
    }
    public function enfants()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
