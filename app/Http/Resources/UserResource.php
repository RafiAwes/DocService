<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "status"=> true,
            "id"=> $this->id,
            "name"=> $this->name,
            "email"=> $this->email,
            "role"=> $this->role,
            "profile_pic"=> $this->profile_pic ? url($this->profile_pic) : null,
            "phone_number"=> $this->phone_number,
            "address"=> $this->address,
            "is_active"=> $this->is_active,
            "email_verified_at"=> $this->email_verified_at,
            "created_at"=> $this->created_at,
            "updated_at"=> $this->updated_at,
        ];
    }
}
