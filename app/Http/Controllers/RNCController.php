<?php

namespace App\Http\Controllers;

class RNCController extends Controller
{
    public function index(string $rnc)
    {

        // Obtenemos la ruta absoluta usando la función helper nativa de Laravel
        $realPath = storage_path('app/public/dgii/extraido/DGII_RNC.csv');

        // Validamos físicamente con PHP si el archivo existe en el disco
        if (!file_exists($realPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'El archivo de la DGII no se encuentra disponible. Ejecuta el comando de descarga primero.'
            ], 503);
        }

        $handle = fopen($realPath, "r");
        $resultado = null;

        if ($handle !== false) {
            // fgetcsv lee línea por línea usando el delimitador '|' de la DGII
            while (($data = fgetcsv($handle, 0)) !== FALSE) {

                // Si la primera columna coincide exactamente con el RNC buscado
                if (isset($data[0]) && trim($data[0]) === trim($rnc)) {

                    // Convertimos la codificación ISO de la DGII a UTF-8 para limpiar acentos y la Ñ
                    $resultado = [
                        'rnc' => trim(mb_convert_encoding($data[0], 'UTF-8', 'ISO-8859-1')),
                        'razon_social' => trim(mb_convert_encoding($data[1], 'UTF-8', 'ISO-8859-1')),
                        'nombre_comercial' => isset($data[2]) ? trim(mb_convert_encoding($data[2], 'UTF-8', 'ISO-8859-1')) : '',
                        'status' => isset($data[2]) ? trim(mb_convert_encoding($data[2], 'UTF-8', 'ISO-8859-1')) : '',
                    ];

                    break; // 🔥 LA CLAVE: Rompe el bucle aquí. No lee ni una sola línea más del archivo.
                }
            }
            fclose($handle); // Cerramos el archivo limpio
        }

        // 4. Si encontramos los datos, respondemos de una vez
        if ($resultado) {
            return response()->json([
                'status' => 'success',
                'data' => $resultado
            ]);
        }

        // 5. Si recorrió todo el archivo y no apareció
        return response()->json([
            'status' => 'error',
            'message' => 'RNC no encontrado en los registros de la DGII.'
        ], 404);

    }
}
