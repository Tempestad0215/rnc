<?php

namespace App\Imports;

use App\Models\RNC;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class RncImport implements ToCollection, WithChunkReading
{
    /**
     * @param Collection $collection
     * @return void
     */
    public function collection(Collection $collection): void
    {

        $insertData = [];
        foreach ($collection as $row)
        {
            if(!isset($row[0]) || !isset($row[1])) {
                continue;
            }

            // Acumulamos los datos limpios en el arreglo
            $insertData[] = [
                'rnc'              => trim($row[0]),
                'razon_social'     => trim($row[1]),
                'actividad' => isset($row[2]) ? trim($row[2]) : '',
                'status' => isset($row[5]) ? trim($row[5]) : '',
                'type' => isset($row[6]) ? trim($row[6]) : '',
                'created_at'       => now(),
                'updated_at'       => now(),
            ];



            // Crear los datos
            if (!empty($insertData)) {
                RNC::upsert(
                    $insertData,
                    ['rnc'], // Columna única para identificar duplicados
                    ['razon_social', 'actividad','status','type'] // Columnas a actualizar si ya existe
                );
            }
        }
    }


    public function chunkSize(): int
    {
        return 5000;
    }
}
