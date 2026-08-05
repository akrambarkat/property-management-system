<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Currency;
use App\Models\Tenant;
use App\Services\DataExportService;
use App\Services\PdfService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function dashboard(ReportService $reportService): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $reportService->getDashboardStats()]);
    }

    public function profitLoss(ReportService $reportService, PdfService $pdfService, Request $request): JsonResponse|StreamedResponse
    {
        $data = $reportService->getProfitLoss(
            $request->building_id,
            $request->from,
            $request->to
        );

        if ($request->query('export') === 'pdf') {
            return $this->exportPdf($pdfService, $data, $request);
        }

        if ($request->query('export') === 'excel') {
            return $this->exportExcel($data);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    private function exportPdf(PdfService $pdfService, array $data, Request $request): StreamedResponse
    {
        $buildingName = null;
        if ($request->building_id) {
            $buildingName = Building::whereKey($request->building_id)->value('name');
        }

        return $pdfService->download('reports.profit-loss', [
            'data' => $data,
            'buildingName' => $buildingName,
            'from' => $request->from,
            'to' => $request->to,
            'currency' => $this->currencySymbol(),
        ], 'profit_loss_'.now()->format('Ymd_His').'.pdf');
    }

    private function exportExcel(array $data): StreamedResponse
    {
        $rows = $data['details'] ?? [];

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="profit_loss_'.now()->format('Ymd_His').'.csv"',
            'Cache-Control' => 'no-store',
        ];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['المبنى', 'الوحدة', 'المستأجر', 'الإيجار', 'المرافق', 'المجموع'], ';');
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['building'] ?? '',
                    $row['unit'] ?? '',
                    $row['tenant'] ?? '',
                    number_format((float) ($row['rent'] ?? 0), 2, '.', ''),
                    number_format((float) ($row['utilities'] ?? 0), 2, '.', ''),
                    number_format((float) ($row['total'] ?? 0), 2, '.', ''),
                ], ';');
            }
            fclose($handle);
        }, 'profit_loss_'.now()->format('Ymd_His').'.csv', $headers);
    }

    private function currencySymbol(): string
    {
        return Currency::where('is_default', true)->value('symbol') ?? 'ر.س';
    }

    public function exportList(DataExportService $exportService, PdfService $pdfService, Request $request): StreamedResponse
    {
        $entity = $request->query('entity', '');
        $format = $request->query('format', 'pdf');
        $params = $request->except(['entity', 'format']);

        $data = $exportService->list($entity, $params, $this->currencySymbol());
        $filename = $entity.'_'.now()->format('Ymd_His');

        return match ($format) {
            'excel' => $pdfService->tableToCsv($data['title'], $data['columns'], $data['rows'], $filename.'.csv'),
            'csv' => $pdfService->tableToCsv($data['title'], $data['columns'], $data['rows'], $filename.'.csv'),
            default => $pdfService->downloadTable($data['title'], $data['columns'], $data['rows'], $filename.'.pdf'),
        };
    }

    public function tenantStatement(ReportService $reportService, PdfService $pdfService, Request $request, Tenant $tenant): StreamedResponse|JsonResponse
    {
        $statement = $reportService->getTenantStatement($tenant->id);
        $filename = 'tenant_statement_'.$tenant->id.'_'.now()->format('Ymd_His');

        if ($request->query('format') === 'excel' || $request->query('format') === 'csv') {
            $columns = [
                ['key' => 'invoice_number', 'label' => 'رقم الفاتورة'],
                ['key' => 'building', 'label' => 'المبنى'],
                ['key' => 'unit', 'label' => 'الوحدة'],
                ['key' => 'issue_date', 'label' => 'الإصدار'],
                ['key' => 'due_date', 'label' => 'الاستحقاق'],
                ['key' => 'total', 'label' => 'الإجمالي'],
                ['key' => 'paid', 'label' => 'المدفوع'],
                ['key' => 'balance', 'label' => 'الرصيد'],
                ['key' => 'status', 'label' => 'الحالة'],
            ];

            return $pdfService->tableToCsv('كشف حساب المستأجر', $columns, $statement['invoices'], $filename.'.csv');
        }

        return $pdfService->download('reports.tenant-statement', [
            'statement' => $statement,
            'currency' => $this->currencySymbol(),
        ], $filename.'.pdf');
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
