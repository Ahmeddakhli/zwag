<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LookupResource extends JsonResource
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
            'slug' => $this->slug,
            'title' => $this->title,
            'translations' => $this->translations, // Current locale translation
            'parent_id' => $this->parent_id,
            'children' => LookupResource::collection($this->children),
            // 'created_at' => $this->created_at->toISOString(),
            // 'updated_at' => $this->updated_at->toISOString(),

        ];
    }
}
