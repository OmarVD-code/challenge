<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\CreditReportExport;
use Maatwebsite\Excel\Facades\Excel;

class CreditReportController extends Controller
{
    public function export(Request $request)
    {
        $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'    => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $fecha_inicio = $request->input('fecha_inicio');
        $fecha_fin    = $request->input('fecha_fin');

        $nombre = "reporte_crediticio_{$fecha_inicio}_{$fecha_fin}.xlsx";

        (new CreditReportExport($fecha_inicio, $fecha_fin))->queue($nombre, 'local');

        return response()->json([
            'message' => 'Reporte en proceso',
            'archivo' => $nombre,
        ]);
    }
}
