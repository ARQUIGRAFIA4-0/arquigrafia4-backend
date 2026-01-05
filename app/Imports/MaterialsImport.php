<?php

namespace App\Imports;

use App\Models\VRACore\VRACMaterial;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MaterialsImport implements ToModel, WithHeadingRow
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

        $subject = new VRACMaterial([
            'id' => $row['id'],
            'label' => $row['label'],
            'type' => $row['type'],
            'vocab' => $row['vocab'],
            'ref_id' => $row['ref_id'],
        ]);

        return $subject;
    }
}
