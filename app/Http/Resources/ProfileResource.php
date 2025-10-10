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
        $loggedUser = $request->user('api');

        $genderCheck = false;
        $birthdateCheck = false;
        $scholarityCheck = false;
        $raceCheck = false;
        $professionCheck = false;
        $addressCheck = false;
        if ($loggedUser && $loggedUser->id == $this->user_id) {
            $genderCheck = true;
            $birthdateCheck = true;
            $scholarityCheck = true;
            $raceCheck = true;
            $professionCheck = true;
            $addressCheck = true;
        } else if ($this->configurations) {
            if (array_key_exists('gender', $this->configurations) && $this->configurations['gender']) $genderCheck = true;
            if (array_key_exists('birthdate', $this->configurations) && $this->configurations['birthdate']) $birthdateCheck = true;
            if (array_key_exists('scholarity', $this->configurations) && $this->configurations['scholarity']) $scholarityCheck = true;
            if (array_key_exists('race', $this->configurations) && $this->configurations['race']) $raceCheck = true;
            if (array_key_exists('profession', $this->configurations) && $this->configurations['profession']) $professionCheck = true;
            if (array_key_exists('address', $this->configurations) && $this->configurations['address']) $addressCheck = true;
        }
        
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'gender' => $genderCheck ? $this->gender : '',
            'birthdate' => $birthdateCheck ? $this->birthdate : '',
            'scholarity' => $scholarityCheck ? $this->scholarity : '',
            'socials' => $this->socials,
            'configurations' => $this->configurations,
            'bio' => $this->bio,
            'race' => $raceCheck ? $this->race : '',
            'profession' => $professionCheck ? $this->profession : '',
            'address' => $addressCheck ? $this->address : '',
            'subjects' => $this->subjects,
        ];
    }
}
