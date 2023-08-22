<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BloodGroup extends Model
{
    use SoftDeletes;
    protected $table = "blood_groups";
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
}