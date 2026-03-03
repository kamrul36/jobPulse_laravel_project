<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // dd($this->createdBy);
         return [
        'category_id' => $this->id,
        'category_name' => $this->name,
        'slug' => $this->slug,
        'icon' => $this->icon,
        'status' => $this->status,

        // 'created_by' => $this->createdBy?->username,
        // 'updated_by' => $this->updatedBy?->username,
        // 'deleted_by' => $this->deletedBy?->username,

        // 'created_at' => $this->created_at,
        // 'updated_at' => $this->updated_at,
    ];
    }
}
