<?php

namespace App\Imports;

use App\Models\VRACore\VRACStylePeriod;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StylePeriodsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        if (!isset($row['label']) || !isset($row['id'])) {
            return null;
        }

        $subject = new VRACStylePeriod([
            'id' => $row['id'],
            'label' => $row['label'],
            'vocab' => $row['vocab'],
            'ref_id' => $row['ref_id'],
        ]);

        return $subject;
    }
}
