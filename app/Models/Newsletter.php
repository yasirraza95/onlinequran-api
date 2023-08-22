<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Newsletter extends Model
{
    use SoftDeletes;
    protected $table = 'newsletter';
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $guarded = ['id'];

    public static function validationRules()
    {
        return [
            'subject' =>
            'required|string|unique:newsletter,subject,NULL,id,deleted_at,NULL',
            'body' => 'required|string',
        ];
    }
}