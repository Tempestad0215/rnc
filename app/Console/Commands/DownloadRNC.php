<?php

namespace App\Console\Commands;

use App\Imports\RncImport;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
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
        $zipPath = $storageDir.'DGII_RNC.zip';
        $extractPath = $storageDir.'/extraido';

        // Mensaje de que vas a iniciar la descarga
        $this->info('1. Iniciando la descarga desde la DGII... (Esto puede tomar un momento)');

        // Verificamos si la carpeta de almacenamiento existe, si no la creamos
        if(!is_dir($storageDir))
        {
            // Creamos la carpeta de almacenamiento
            mkdir($storageDir, 0755, true);
        }

        // Descargamos el archivo desde la URL
        $response = Http::withoutVerifying()->timeout(300)->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ])->get($url);


        // Verificamos si la descarga fue exitosa
        if (!$response->successful()) {
            $this->error('Error al descargar el archivo: '.$response->status());
            return;
        }

        // Guadar el archivo en la carpeta de almacenamiento
        file_put_contents($zipPath, $response->body());
        $this->info('-> Archivo ZIP descargado exitosamente.');

        // Extraer el archivo ZIP
        $this->info('2. Descomprimiendo el archivo...');
        $zip = new ZipArchive();

        // Verificar que el archivo exista
        if($zip->open($zipPath) === TRUE)
        {
            $zip->extractTo($extractPath);
            $zip->close();
            $this->info('-> Archivo ZIP descomprimido exitosamente.');
        }
        else
        {
            $this->error('Error al descomprimir el archivo ZIP.');
            return;
        }

        // Para verificar si existen los archivos
        $archivoExtraido = scandir($extractPath);
        $archivoOriginal = null;

        // Verificar que el archivo exista
        foreach($archivoExtraido as $archivo){
            if(str_ends_with($archivo, '.csv') || str_ends_with($archivo, '.txt')){
                $archivoOriginal = $extractPath.'/'.$archivo;
                break;
            }
        }

        // Verificar que el archivo exista
        if (!$archivoOriginal || !file_exists($archivoOriginal)) {
            $this->error('Error: No se encontró ningún archivo CSV o TXT dentro de la carpeta de extracción.');
            return;
        }

        // Ruta del archivo CSV
        $csvFile = $extractPath.'/DGII_RNC.csv';

        // Renombrar el archivo
        if(rename($archivoOriginal, $csvFile)){
            $this->info('Archivo CSV renombrado exitosamente.');
        }else{
            $this->error('Error al renombrar el archivo CSV.');
        }


        // Gudar los datos
        $this->info('3. Guardando los datos en la base de datos...');
        Excel::import(new RncImport, $csvFile);
        $this->info('-> Datos guardados exitosamente.');

    }
}
