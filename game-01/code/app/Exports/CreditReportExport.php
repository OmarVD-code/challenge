<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class CreditReportExport implements FromQuery, WithHeadings, WithChunkReading, ShouldQueue, ShouldAutoSize, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */

    use Exportable;

    public function __construct(
        private string $fecha_inicio,
        private string $fecha_fin
    ) {}

    public function collection()
    {
        //
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

    public function query()
    {
        $fecha_inicio = '2026-01-01';
        $fecha_fin    = '2026-01-15';

        $od = DB::table('report_other_debts')
            ->selectRaw("
                subscription_report_id,
                1 AS has_other_debt,
                GROUP_CONCAT(entity ORDER BY id SEPARATOR ' | ') AS company,
                MAX(expiration_days) AS expiration_days,
                SUM(amount) AS total_amount
            ")
            ->groupBy('subscription_report_id');

        $l = DB::table('report_loans')
            ->selectRaw("
                subscription_report_id,
                1 AS has_loan,
                GROUP_CONCAT(DISTINCT status ORDER BY id SEPARATOR ' | ') AS situation
            ")
            ->groupBy('subscription_report_id');

        $cc = DB::table('report_credit_cards')
            ->selectRaw("
                subscription_report_id,
                1 AS has_credit_card,
                GROUP_CONCAT(bank ORDER BY id SEPARATOR ' | ') AS entity,
                SUM(line) AS total_line,
                SUM(used) AS used_line,
                MAX(created_at) AS created_at
            ")
            ->groupBy('subscription_report_id');

        $query = DB::table('subscription_reports as sr')
            ->join('subscriptions as s', 's.id', '=', 'sr.subscription_id')
            ->leftJoinSub(
                $od,
                'od',
                fn($join) =>
                $join->on('od.subscription_report_id', '=', 'sr.id')
            )
            ->leftJoinSub(
                $l,
                'l',
                fn($join) =>
                $join->on('l.subscription_report_id', '=', 'sr.id')
            )
            ->leftJoinSub(
                $cc,
                'cc',
                fn($join) =>
                $join->on('cc.subscription_report_id', '=', 'sr.id')
            )
            ->whereBetween('sr.created_at', [
                $fecha_inicio . ' 00:00:00',
                $fecha_fin . ' 23:59:59',
            ])
            ->selectRaw("
                sr.id AS report_id,
                s.full_name,
                s.document AS dni,
                s.email,
                s.phone,
                od.company,
                TRIM(BOTH ' | ' FROM CONCAT_WS(' | ',
                    IF(od.has_other_debt = 1, 'OTHER_DEBT', NULL),
                    IF(l.has_loan = 1, 'LOAN', NULL),
                    IF(cc.has_credit_card = 1, 'CREDIT_CARD', NULL)
                )) AS type_debt,
                l.situation,
                od.expiration_days,
                cc.entity,
                od.total_amount,
                cc.total_line,
                cc.used_line,
                cc.created_at,

                'EXPIRED' AS status
            ");

        return $query;
    }

    public function map($row): array
    {
        return [
            $row->reporte_id,
            $row->nombre_completo,
            $row->dni,
            $row->email,
            $row->telefono,
            $row->compania,
            $row->tipo_deuda,
            $row->situacion,
            $row->dias_atraso,
            $row->entidad,
            $row->monto_total,
            $row->linea_total,
            $row->linea_usada,
            $row->reporte_subido_el,
            $row->estado,
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }
}
