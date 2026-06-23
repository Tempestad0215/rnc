<?php

namespace App\Imports;

use App\Models\RNC;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class RncImport implements ToCollection, WithChunkReading
{
    /**
     * @param Collection $collection
     * @return void
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $row)
        {
            if(!isset($row[0]) || !isset($row[1])) {
                continue;
            }

            $rnc = trim($row[0]);
            $razonSocial = trim($row[1]);
            $nombreComercial = isset($row[2]) ? trim($row[2]) : '';
            $status = isset($row[5]) ? trim($row[5]) : '';
            $type = isset($row[6]) ? trim($row[6]) : '';

            // Crear los datos
            RNC::updateOrCreate([
                'rnc' => $rnc,
            ],[
                'razon_social' => $razonSocial,
                'actividad' => $nombreComercial,
                'status' => $status,
                'type' => $type,
            ]);
        }
    }


    public function chunkSize(): int
    {
        return 5000;
    }
}
