<?php

namespace App\Http\Controllers;

use App\CallHistory221122;
use App\Services\CallStatsNewService;
use App\Services\DatatableService;
use App\Services\FileGenerationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\OffersQueue;
use App\CallHistory;
use Illuminate\Support\Facades\Redis;
use DB;

class LeadsController extends Controller
{
    private $datatableService;
    private $fileGenerationService;

    public function __construct(DatatableService $datatableService,FileGenerationService $fileGenerationService)
    {
        $this->datatableService = $datatableService;
        $this->fileGenerationService = $fileGenerationService;
    }

    public function index()
    {
        $today = date("M d");

        return view('leads',compact('today'));
    }

    public function sendedLeadsAjax(Request $request,CallStatsNewService $callStatsNewService)
    {
        $dateRange = [Carbon::now()->subDay()->startOfDay(),Carbon::now()->subDay()->endOfDay()];
        if (isset ($request->datarange))
            $dateRange = $callStatsNewService->formatDate($request->datarange);

        $leads = OffersQueue::select(array('*',DB::raw('COUNT(*) as count')))->whereBetween('datetime',$dateRange)->groupBy('project_id','partner')->get();

        return $this->datatableService->cunstractDatatable($leads);
    }

    public function findByPhoneAjax(Request $request)
    {
        $callHistory = CallHistory::select(
            "call_history.id AS id",
            "call_history.created_at",
            "call_history.phone",
            "voip_operator",
            "operator",
            "name",
            "email",
            "server_id",
            "script",
            "price",
            "duration")
            ->join('profil_tasks','profil_tasks.phone','=','call_history.phone')
            ->where('call_history.phone',$request->phone)
            ->get();

        return $this->datatableService->cunstractDatatable($callHistory);
    }

    public function generateWord($phone, Request $request)
    {
        $file = $this->fileGenerationService->generateWord($phone,$request);

        return response()->download($file)->deleteFileAfterSend('true');
    }

    public function generateScreenShot($phone, Request $request)
    {
        $file = $this->fileGenerationService->generateScreenShot($phone,$request);

        return response()->download($file)->deleteFileAfterSend('true');
    }
}
