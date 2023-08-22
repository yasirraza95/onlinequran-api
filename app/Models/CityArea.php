<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CityArea extends Model
{
    use SoftDeletes;
    protected $table = "city_area";
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
}