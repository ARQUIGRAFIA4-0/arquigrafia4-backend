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
        if (!isset($row['term']) || !isset($row['id'])) {
            return null;
        }

        $subject = new VRACSubject([
            'id' => $row['id'],
            'term' => $row['term'],
            'type' => 'otherTopic',
            'vocab' => $row['vocab'],
            'ref_id' => $row['ref_id'],
        ]);

        return $subject;
    }
}
