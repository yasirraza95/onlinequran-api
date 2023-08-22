<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class State extends Model
{
    use SoftDeletes;
    protected $table = 'states';
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
}