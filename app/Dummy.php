<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dummy extends Model
{
    protected $table = ('dummies');
    protected $fillable = ['firstname', 'start', 'end'];
}
