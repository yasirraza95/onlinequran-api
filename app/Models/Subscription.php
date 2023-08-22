<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;
    protected $table = 'subscription';
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
}