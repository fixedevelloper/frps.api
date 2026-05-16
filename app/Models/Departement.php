<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    protected $fillable = ['name'];

    /**
     * Un département possède plusieurs villes.
     */
    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
