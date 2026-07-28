<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function dashboard(ReportService $reportService): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $reportService->getDashboardStats()]);
    }

    public function profitLoss(ReportService $reportService, Request $request): JsonResponse
    {
        $data = $reportService->getProfitLoss(
            $request->building_id,
            $request->from,
            $request->to
        );
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function income(ReportService $reportService, Request $request): JsonResponse
    {
        $data = $reportService->getProfitLoss($request->building_id, $request->from, $request->to);
        return response()->json(['success' => true, 'data' => [
            'total_rent' => $data['total_rent'],
            'total_utilities' => $data['total_utilities'],
            'total_income' => $data['total_income'],
        ]]);
    }

    public function expenses(ReportService $reportService, Request $request): JsonResponse
    {
        $data = $reportService->getProfitLoss($request->building_id, $request->from, $request->to);
        return response()->json(['success' => true, 'data' => [
            'expenses_by_category' => $data['expenses_by_category'],
            'total_expenses' => $data['total_expenses'],
        ]]);
    }
}
