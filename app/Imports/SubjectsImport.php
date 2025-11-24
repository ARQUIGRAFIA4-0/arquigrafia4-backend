<?php

namespace App\Imports;

use App\Models\VRACore\VRACSubject;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SubjectsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        if (!isset($row['name']) || !isset($row['id'])) {
            return null;
        }

        $subject = new VRACSubject([
            'term' => $row['name'],
            'type' => 'otherTopic',
            'vocab' => 'ARQUIGRAFIA',
            'ref_id' => $row['id'],
        ]);

        return $subject;
    }
}
