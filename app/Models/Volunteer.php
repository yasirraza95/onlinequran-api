<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Volunteer extends Model
{
    use SoftDeletes;
    protected $table = 'volunteers';
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
}
