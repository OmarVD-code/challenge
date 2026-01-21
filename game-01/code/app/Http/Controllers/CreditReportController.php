<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\CreditReportExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Actions\ExportCreditReportAction;
class CreditReportController extends Controller
{
    public function export(Request $request, ExportCreditReportAction $action)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'    => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $result = $action->handle(
            $data['fecha_inicio'],
            $data['fecha_fin']
        );

        return response()->json([
            'message' => 'Reporte en proceso',
            'archivo' => $result->file_name,
        ]);
    }
}
