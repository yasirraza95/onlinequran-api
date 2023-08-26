<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;
    protected $table = "services";
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
}