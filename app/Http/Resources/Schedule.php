<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Schedule extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'start_time' => $this->start_time
        ];
    }
}
