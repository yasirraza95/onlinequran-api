<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slider extends Model
{
    use SoftDeletes;
    protected $table = "sliders";
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
}