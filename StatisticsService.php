<?php

namespace App\Services;

use App\CallHistory;
use App\OffersQueue;
use App\Profile;
use App\ReportSetting;
use App\SovcomBank;
use App\Services\DatatableService;
use DB;

class StatisticsService
{
    public function getDatatable(string $date, array $select, $settings_id = 1,$groupBy = null,$andWhere = null) : string
    {
        $dateRange = $this->formatDate($date);

        return $this->dbQuery($select,$dateRange,$settings_id,$groupBy,$andWhere);
    }

    public function dbQuery(array $select,array $dateRange,$settings_id,$groupBy = null,$andWhere = null) : string
    {
        $groupBy = $groupBy ?? $select;
        $select[] = DB::raw('COUNT(*) as count,COUNT(if(status = 1, status, NULL)) AS success,CAST(SUM(price) AS DECIMAL(12,2)) AS price,CAST(AVG(if(status = 1, duration, NULL)) AS DECIMAL(12,2)) AS duration,COUNT(if(status = 1, status, NULL))/COUNT(*)*100 as ASR,
                      SUM(IF(offers_queue.id IS NOT NULL, 1, 0)) cnt_leads');
        $settings = ReportSetting::whereId($settings_id)->first();
        $arts2 = CallHistory::select($select)
            ->leftJoin('offers_queue', function ($join) use ($settings,$dateRange) {
                $join->on('offers_queue.phone','=','call_history.phone')
                    ->whereBetween('offers_queue.datetime', $dateRange)
                    ->where('offers_queue.partner','=',$settings->partner)
                    ->where('offers_queue.project_id', '=', $settings->project_id);
            })->whereBetween('created_at',$dateRange)
            ->whereIn('script',json_decode($settings->script))
            ->groupBy($groupBy);

        if (!is_null($andWhere))
            $arts2->where($andWhere);

        $response = $arts2->get();

        return (new DatatableService())->cunstractDatatable($response);
    }

    public function formatDate(string $date) : array
    {
        if ($date == 'today')
        {
            return [date("Y-m-d").' 00:00:00',date("Y-m-d").' 23:59:59'];
        }
        $dateArray = explode(' - ',$date);
        $from = date("Y-m-d H:i", strtotime($dateArray[0]));
        $to = date("Y-m-d H:i", strtotime($dateArray[1]));

        return [$from,$to];
    }
}
