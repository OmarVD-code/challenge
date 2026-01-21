<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class CreditReportQuery
{
    public function build(string $fecha_inicio, string $fecha_fin)
    {
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

        return DB::table('subscription_reports as sr')
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
    }
}
