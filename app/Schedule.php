<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $table = ('schedules');
    protected $fillable = ['movie_id','studio_id','start','end','price'];
}
