<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $genderCheck = false;
        if (array_key_exists('gender', $this->configurations) && $this->configurations['gender']) $genderCheck = true;
        $birthdateCheck = false;
        if (array_key_exists('birthdate', $this->configurations) && $this->configurations['birthdate']) $birthdateCheck = true;
        $phoneCheck = false;
        if (array_key_exists('phone', $this->configurations) && $this->configurations['phone']) $phoneCheck = true;
        $scholarityCheck = false;
        if (array_key_exists('scholarity', $this->configurations) && $this->configurations['scholarity']) $scholarityCheck = true;
        
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'gender' => $genderCheck ? $this->gender : '',
            'birthdate' => $birthdateCheck ? $this->birthdate : '',
            'phone' => $phoneCheck ? $this->phone : '',
            'scholarity' => $scholarityCheck ? $this->scholarity : '',
            'website' => $this->website,
            'socials' => $this->socials,
            'configurations' => $this->configurations,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
        ];
    }
}
