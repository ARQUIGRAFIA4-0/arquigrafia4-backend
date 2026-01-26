<?php

namespace App\Imports;

use App\Models\Profile;
use App\Models\User;
use App\Models\VRACore\VRACContributorName;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;

use function Symfony\Component\Clock\now;

class LegacyUsersImport implements OnEachRow, WithHeadingRow
{
    public function onRow(Row $row)
    {
        $row = $row->toArray();
        echo("{$row['id']} ");

        if (!$row['email']) {
            return null;
        }
        if (!$row['password']) {
            $row['password'] = Hash::make('UmaSenhaSimples');
        }

        try {
            $fullName = Str::ucwords(Str::lower($row['name'] . (isset($row['last_name']) ? " {$row['last_name']}" : "")));
            $user = new User();
            $user->name = $fullName;
            $user->id = $row['uuid'];
            $user->email = $row['email'];
            $user->password = $row['password'];
            $user->legacy_id = $row['id'];
            $user->email_verified_at = $row['active'] == 'yes' ? now() : null;
            if (isset($row['created_at'])) {
                $user->created_at = $row['created_at'];
            }
            if (isset($row['updated_at'])) {
                $user->updated_at = $row['updated_at'];
            }
            $user->save();

            // creates Profile
            $profile = new Profile();
            $profile->user_id = $user->id;
            $configurations = [];
            if (isset($row['gender'])) {
                $profile->gender = Str::substr($row['gender'], 0, 20);
                $configurations['gender'] = false;
            }
            if (isset($row['birthday'])) {
                $profile->birthdate = $row['birthday'];
                $configurations['birthdate'] = false;
            }
            if (isset($row['scholarity'])) {
                $profile->scholarity = Str::substr($row['scholarity'], 0, 20);
                $configurations['scholarity'] = false;
            }
            if (isset($row['city'])) {
                $profile->address = Str::substr("{$row['city']} {$row['state']} {$row['country']}", 0, 255);
                $configurations['address'] = false;
            }
            $profile->configurations = $configurations;
            $profile->save();

            // creates Contributor
            $contributor = new VRACContributorName();
            $contributor->name = $fullName;
            $contributor->type = 'personal';
            $contributor->vocab = 'ARQUIGRAFIA';
            $contributor->ref_id = $user->id;
            $contributor->user_id = $user->id;
            $contributor->save();
        } catch (\Throwable $th) {
            echo(" erro: " . $th->getMessage());
        }

        echo("\n");
        return null;
    }
}

// id	uuid	name	last_name	login	gender	email	password	oldPassword	oldAccount	country	state	city	address	
// birthday	scholarity	language	photo	phone	site	id_facebook	id_instagram	id_twitter	
// remember_token	created_at	updated_at	id_stoa	visibleBirthday	visibleEmail	invitations	verify_code	active	nb_eval	mobile_token
