<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MaritalStatusResource extends JsonResource
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
            'title' => $this->title,
            'translations' => $this->translations, // Current locale translation
            // 'created_at' => $this->created_at->toISOString(),
            // 'updated_at' => $this->updated_at->toISOString(),

        ];
    }
}
