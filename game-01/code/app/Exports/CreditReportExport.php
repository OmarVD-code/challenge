<?php

namespace App\Exports;

use App\Queries\CreditReportQuery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Events\AfterSheet;

class CreditReportExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithChunkReading,
    ShouldQueue,
    ShouldAutoSize,
    WithEvents
{
    use Exportable;

    public function __construct(
        private string $fecha_inicio,
        private string $fecha_fin,
        private ?CreditReportQuery $creditReportQuery = null
    ) {
        $this->creditReportQuery ??= new CreditReportQuery();
    }

    public function query()
    {
        return $this->creditReportQuery->build($this->fecha_inicio, $this->fecha_fin);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre Completo',
            'DNI',
            'Email',
            'Teléfono',
            'Compañía',
            'Tipo de deuda',
            'Situación',
            'Atraso',
            'Entidad',
            'Monto total',
            'Línea total',
            'Línea usada',
            'Reporte subido el',
            'Estado',
        ];
    }

    public function map($row): array
    {
        return [
            $row->report_id,
            $row->full_name,
            $row->dni,
            $row->email,
            $row->phone,
            $row->company,
            $row->type_debt,
            $row->situation,
            $row->expiration_days,
            $row->entity,
            $row->total_amount,
            $row->total_line,
            $row->used_line,
            $row->created_at,
            $row->status,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastColumn = 'O';
                $event->sheet->getDelegate()->setAutoFilter("A1:{$lastColumn}1");
                $event->sheet->getDelegate()->freezePane('A2');
            },
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }
}
