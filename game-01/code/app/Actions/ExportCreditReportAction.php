<?php

namespace App\Actions;

use App\Exports\CreditReportExport;

class ExportCreditReportAction
{
    public function handle(string $fecha_inicio, string $fecha_fin): object
    {
        $fileName = "reporte_crediticio_{$fecha_inicio}_{$fecha_fin}.xlsx";

        $path = "reports/{$fileName}";

        (new CreditReportExport($fecha_inicio, $fecha_fin))
            ->queue($path, 'local');

        return (object) [
            'file_name' => $fileName,
            'path'      => $path,
            'disk'      => 'local',
        ];
    }
}
