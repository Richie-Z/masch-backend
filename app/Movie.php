<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $table = ('movies');
    protected $fillable = ['name','minute_length','picture_url'];
}
