<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Namaz extends Model
{
    use SoftDeletes;
    protected $table = "namaz_timings";
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $guarded = ['id'];

    // public static function validationRules()
    // {
    //     return [
    //         'blood_id' => 'required|numeric',
    //         'created_by' => 'required|numeric',
    //         'type' => 'required|in:normal,emergency',
    //     ];
    // }
}