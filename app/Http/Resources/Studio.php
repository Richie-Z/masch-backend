<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Studio extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'branch_id' => $this->branch_id,
            'basic_price' => $this->basic_price,
            "aditional_friday_price" => $this->aditional_friday_price,
            "aditional_saturday_price" => $this->aditional_friday_price,
            "aditional_sunday_price" => $this->aditional_friday_price,
        ];
    }
}
