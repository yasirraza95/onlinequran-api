<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use SoftDeletes;
    protected $table = "site_info";
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
}