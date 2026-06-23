<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DownloadRNC extends Command
{
    protected $signature = 'download:rnc';

    protected $description = 'Comando para descargar los archivos de la DGI, dejarlo disponible para poder descargar el archivo de contribuyentes';

    public function handle(): void
    {
        $this->info('¡Comando listo para procesar los datos!');
    }
}
