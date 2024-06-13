<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\ProductlistResource;

class ChildcategoryResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
      return [
        'id' => $this->id,
        'subcategory_id' => $this->subcategory_id,
        'name' => $this->name,
        'attributes' => route('attibutes', $this->id) . '?type=childcategory',
        'products' => ProductlistResource::collection($this->products()->where('status', 1)->get()),
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
      ];
    }
}
