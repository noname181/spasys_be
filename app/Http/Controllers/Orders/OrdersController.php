<?php

namespace App\Http\Controllers\Orders;

use App\Utils\Messages;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    /**
     * Settlement Amount Trend
     * @return \Illuminate\Http\JsonResponse
     */
    public function settlementAmountTrend(Request $request)
    {
        try {
            $end_date = Date::parse($request['date']);
            $start_date = Date::parse($request['date'])->subDays(29);

            $crquery = Orders::select([
                DB::raw("COUNT(id) as y"),
                DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d') as x")
            ])
                ->where('created_at', '>=', $start_date)
                ->where('created_at', '<=', $end_date)
                ->where('status', 'CANCELED')
                ->orWhere('status', 'REFUNDED')
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"))
                ->orderBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"), 'ASC')
                ->get();

            $cquery = Orders::select([
                DB::raw("COUNT(id) as y"),
                DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d') as x")
            ])
                ->where('created_at', '>=', $start_date)
                ->where('created_at', '<=', $end_date)
                ->where('status', 'COMPLETED')
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"))
                ->orderBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"), 'ASC')
                ->get();

            $pquery = Orders::select([
                DB::raw("COUNT(id) as y"),
                DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d') as x")
            ])
                ->where('created_at', '>=', $start_date)
                ->where('created_at', '<=', $end_date)
                ->where('status', 'PAID')
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"))
                ->orderBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"), 'ASC')
                ->get();


            $labels = [];
            $canceled_refunded = [];
            $completed = [];
            $paid = [];
            while($start_date <= $end_date) {
                $date = $start_date->format('Y.m.d');
                $labels[] = $date;

                $canceled_refunded[] = $this->findData($crquery, $date);
                $completed[] = $this->findData($cquery, $date);
                $paid[] = $this->findData($pquery, $date);

                $start_date = $start_date->addDays();
            }

            return response()->json([
                'canceled_refunded' => $canceled_refunded,
                'completed' => $completed,
                'paid' => $paid,
                'labels' => $labels,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

        /**
     * Settlement Amount Trend By Month
     * @return \Illuminate\Http\JsonResponse
     */
    public function settlementAmountTrendByMonth(Request $request)
    {
        try {
            $start_date = Date::parse($request['date']);
            $end_date = Date::parse($request['date'])->addMonths(12);

            $crquery = Orders::select([
                DB::raw("COUNT(id) as y"),
                DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d') as x")
            ])
                ->where('created_at', '>=', $start_date)
                ->where('created_at', '<=', $end_date)
                ->where('status', 'CANCELED')
                ->orWhere('status', 'REFUNDED')
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"))
                ->orderBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"), 'ASC')
                ->get();

            $cquery = Orders::select([
                DB::raw("COUNT(id) as y"),
                DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d') as x")
            ])
                ->where('created_at', '>=', $start_date)
                ->where('created_at', '<=', $end_date)
                ->where('status', 'COMPLETED')
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"))
                ->orderBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"), 'ASC')
                ->get();

            $pquery = Orders::select([
                DB::raw("COUNT(id) as y"),
                DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d') as x")
            ])
                ->where('created_at', '>=', $start_date)
                ->where('created_at', '<=', $end_date)
                ->where('status', 'PAID')
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"))
                ->orderBy(DB::raw("DATE_FORMAT(created_at, '%Y.%m.%d')"), 'ASC')
                ->get();


            $labels = [];
            $canceled_refunded = [];
            $completed = [];
            $paid = [];
            while($start_date <= $end_date) {
                $date = $start_date->format('Y.m.d');    
                $date_label = $start_date->format('Y년 m월');     
                if (!in_array($date_label, $labels)) {
                    $labels[] = $date_label;
                }

                $canceled_refunded[] = $this->findData($crquery, $date);
                $completed[] = $this->findData($cquery, $date);
                $paid[] = $this->findData($pquery, $date);

                $start_date = $start_date->addDays();
            }

            return response()->json([
                'canceled_refunded' => $canceled_refunded,
                'completed' => $completed,
                'paid' => $paid,
                'labels' => $labels,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    private function findData ($array, $k) {
        foreach($array as $v) {
            if($v['x'] === $k) {
                return $v['y'];
            }
        }
        return 0;
    }

}
