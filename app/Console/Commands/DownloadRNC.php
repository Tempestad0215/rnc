<?php

namespace App\Console\Commands;

use App\Models\RNC;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class DownloadRNC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:download-r-n-c';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Para descargar el archivo en formato CSV desde la web de la dgi';

    /**
     * Execute the console command.
     * @throws ConnectionException
     */
    public function handle(): void
    {
        // Url para consultar los datos
        $url = "https://dgii.gov.do/app/WebApps/Consultas/RNC/RNC_CONTRIBUYENTES.zip";

        // Creamos la ruta para poder almacenar los datos
        $storageDir = Storage::disk('dgii')->path('');
        $zipPath = $storageDir . 'DGII_RNC.zip';
        $extractPath = $storageDir . '/extraido';

        // Mensaje de que vas a iniciar la descarga
        $this->info('1. Iniciando la descarga desde la DGII... (Esto puede tomar un momento)');

        // Verificamos si la carpeta de almacenamiento existe, si no la creamos
        if (!is_dir($storageDir)) {
            // Creamos la carpeta de almacenamiento
            mkdir($storageDir, 0755, true);
        }

        // Descargamos el archivo desde la URL
        $response = Http::withoutVerifying()->timeout(300)->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ])->get($url);


        // Verificamos si la descarga fue exitosa
        if (!$response->successful()) {
            $this->error('Error al descargar el archivo: ' . $response->status());
            return;
        }

        // Guadar el archivo en la carpeta de almacenamiento
        file_put_contents($zipPath, $response->body());
        $this->info('-> Archivo ZIP descargado exitosamente.');

        // Extraer el archivo ZIP
        $this->info('2. Descomprimiendo el archivo...');
        $zip = new ZipArchive();

        // Verificar que el archivo exista
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
            $this->info('-> Archivo ZIP descomprimido exitosamente.');
        } else {
            $this->error('Error al descomprimir el archivo ZIP.');
            return;
        }

        // Para verificar si existen los archivos
        $archivoExtraido = scandir($extractPath);
        $archivoOriginal = null;

        // Verificar que el archivo exista
        foreach ($archivoExtraido as $archivo) {
            if (str_ends_with($archivo, '.csv') || str_ends_with($archivo, '.txt')) {
                $archivoOriginal = $extractPath . '/' . $archivo;
                break;
            }
        }

        // Verificar que el archivo exista
        if (!$archivoOriginal || !file_exists($archivoOriginal)) {
            $this->error('Error: No se encontró ningún archivo CSV o TXT dentro de la carpeta de extracción.');
            return;
        }

        // Ruta del archivo CSV
        $csvFile = $extractPath . '/DGII_RNC.csv';

        // Renombrar el archivo
        if (rename($archivoOriginal, $csvFile)) {
            $this->info('Archivo CSV renombrado exitosamente.');
        } else {
            $this->error('Error al renombrar el archivo CSV.');
        }


        // Gudar los datos
        $this->info('3. Guardando los datos en la base de datos...');
        if (!file_exists($csvFile)) {
            $this->error('Error: El archivo CSV no existe en la ruta.');
            return;
        }

        $handle = fopen($csvFile, 'r');
        if ($handle === false) {
            $this->error('Error al abrir el archivo CSV.');
            return;
        }

        // Saltamos la primera línea (Cabecera/Títulos)
        fgetcsv($handle, 0);

        $insertData = [];
        $batchSize = 2500; // Enviamos de 5,000 en 5,000 a la base de datos
        $count = 0;

        DB::disableQueryLog();

        // Leemos línea por línea directamente desde el disco duro
        // El tercer parámetro es ',' (delimitador) y el cuarto es '"' (enclosure nativo)
        while (($row = fgetcsv($handle, 0, ',', '"')) !== false) {
            if (!isset($row[0]) || !isset($row[1])) {
                continue;
            }

            $rnc = trim($row[0]);
            if (empty($rnc) || $rnc === 'RNC') {
                continue;
            }

            // Limpieza de caracteres con iconv (evita el error de bytes en Postgres)
            $razonSocial = trim(iconv('ISO-8859-1', 'UTF-8//IGNORE', $row[1]));
            $nombreComercial = isset($row[2]) ? trim(iconv('ISO-8859-1', 'UTF-8//IGNORE', $row[2])) : '';
            $status = isset($row[4]) ? trim(iconv('ISO-8859-1', 'UTF-8//IGNORE', $row[4])) : '';
            $type = isset($row[5]) ? trim(iconv('ISO-8859-1', 'UTF-8//IGNORE', $row[5])) : '';

            // Forzar codificación UTF-8 limpia para PostgreSQL
            $razonSocial = mb_convert_encoding($razonSocial, 'UTF-8', 'UTF-8');
            $nombreComercial = mb_convert_encoding($nombreComercial, 'UTF-8', 'UTF-8');

            $insertData[] = [
                'rnc' => $rnc,
                'razon_social' => $razonSocial,
                'actividad' => $nombreComercial,
                'status' => $status,
                'type' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $count++;

            if ($count % $batchSize === 0) {
                \App\Models\RNC::upsert(
                    $insertData,
                    ['rnc'],
                    ['razon_social', 'actividad', 'status', 'type', 'updated_at']
                );
                $insertData = [];
                $this->info("-> Procesados {$count} registros...");
            }
        }

        // Insertar el último bloque restante si quedó algo en el arreglo
        if (!empty($insertData)) {
            RNC::upsert(
                $insertData,
                ['rnc'],
                ['razon_social', 'actividad', 'status', 'type', 'updated_at']
            );
        }

        fclose($handle);
        $this->info('-> ¡Todos los datos guardados exitosamente en la Base de Datos!');

    }
}
