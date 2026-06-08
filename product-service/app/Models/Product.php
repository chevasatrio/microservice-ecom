<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id','code','name','description','price','stock'];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($model) => $model->id ??= Str::uuid()->toString());
    }
}