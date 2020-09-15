<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Studio extends Model
{
    protected $table = ('studios');
    protected $fillable = ['name', 'branch_id', 'basic_price', 'aditional_friday_price', 'aditional_saturday_price', 'aditional_sunday_price'];
}
