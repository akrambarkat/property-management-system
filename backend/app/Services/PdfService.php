<?php

namespace App\Services;

use Illuminate\Support\Facades\View as ViewFactory;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfService
{
    /**
     * Build an mPDF instance configured for Arabic/RTLS documents.
     */
    protected function buildMpdf(array $options = []): Mpdf
    {
        $config = array_merge([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 11,
            'margin_top' => 20,
            'margin_right' => 15,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ], $options);

        return new Mpdf($config);
    }

    /**
     * Download a given blade view as a PDF download (attachment) response.
     */
    public function download(string $view, array $data, string $filename, array $options = []): StreamedResponse
    {
        $html = $this->renderToString($view, $data);

        return response()->streamDownload(function () use ($html, $filename, $options) {
            $mpdf = $this->buildMpdf($options);
            $mpdf->WriteHTML($html);
            echo $mpdf->Output($filename, 'S');
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Download a table of rows as a PDF using the generic table view.
     */
    public function downloadTable(string $title, array $columns, $rows, string $filename, string $currency = 'ر.س'): StreamedResponse
    {
        $html = $this->renderToString('reports.table', [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
            'currency' => $currency,
        ]);

        return response()->streamDownload(function () use ($html, $filename) {
            $mpdf = $this->buildMpdf(['orientation' => 'L']);
            $mpdf->WriteHTML($html);
            echo $mpdf->Output($filename, 'S');
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Render a whole table as CSV (UTF-8 with BOM, Excel-friendly).
     */
    public function tableToCsv(string $title, array $columns, $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($columns, $rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_column($columns, 'label'), ';');
            foreach ($rows as $row) {
                fputcsv($handle, array_values($row), ';');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Render a blade view into a string with RTL/AR language wrapper.
     */
    public function renderToString(string $view, array $data): string
    {
        $html = ViewFactory::make($view, $data)->render();

        return $this->wrapRtlDocument($html);
    }

    /**
     * Build a full HTML document with Arabic RTL attributes and base styles.
     */
    protected function wrapRtlDocument(string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<style>
    body { font-family: dejavusans; color: #1f2937; line-height: 1.5; }
    h1, h2, h3 { margin: 0 0 8px; color: #111827; }
    h1 { font-size: 18px; }
    h2 { font-size: 14px; }
    .text-muted { color: #6b7280; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 10px; }
    th, td { padding: 6px 8px; border: 1px solid #d1d5db; text-align: right; }
    th { background: #f3f4f6; color: #374151; font-weight: bold; }
    .summary { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .summary td { border: none; padding: 3px 0; font-size: 11px; }
    .summary .label { color: #4b5563; }
    .summary .value { font-weight: 700; }
    .highlight { border-top: 2px solid #2563eb; }
    .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #9ca3af; }
</style>
</head>
<body>
{$body}
<div class="footer">Generated {$this->generatedDate()} — EMAARPlus</div>
</body>
</html>
HTML;
    }

    protected function generatedDate(): string
    {
        return now()->format('d/m/Y H:i');
    }
}
