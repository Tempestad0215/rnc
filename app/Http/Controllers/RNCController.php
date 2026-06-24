<?php

namespace App\Http\Controllers;

use App\Models\RNC;

class RNCController extends Controller
{
    public function index(string $rnc)
    {

        // Obtenemos la ruta absoluta usando la función helper nativa de Laravel
        $rnc = RNC::where('rnc', $rnc)->first();

        if(!$rnc) {
            return response()->json(['message' => 'RNC not found'], 404);
        }

        return response()->json($rnc);

    }
}
