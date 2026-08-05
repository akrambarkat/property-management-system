<div class="report-header">
    <h1>تقرير الأرباح والخسائر</h1>
    <div class="text-muted report-meta">
        @if($buildingName)
            المبنى: <strong>{{ $buildingName }}</strong> &nbsp;|&nbsp;
        @endif
        @if($from) من: {{ $from }} &nbsp;@endif
        @if($to) إلى: {{ $to }} &nbsp;@endif
    </div>
</div>

<h2>الملخص</h2>
<table class="summary">
    <tr>
        <td class="label">إجمالي الإيجارات</td>
        <td class="value">{{ $currency }} {{ number_format($data['total_rent'], 2) }}</td>
        <td class="label">إجمالي المرافق</td>
        <td class="value">{{ $currency }} {{ number_format($data['total_utilities'], 2) }}</td>
    </tr>
    <tr>
        <td class="label">إجمالي الدخل</td>
        <td class="value">{{ $currency }} {{ number_format($data['total_income'], 2) }}</td>
        <td class="label">إجمالي المصروفات</td>
        <td class="value">{{ $currency }} {{ number_format($data['total_expenses'], 2) }}</td>
    </tr>
    <tr>
        <td class="label">صافي الربح / الخسارة</td>
        <td class="value">{{ $currency }} {{ number_format($data['net_profit'], 2) }}</td>
        <td></td>
        <td></td>
    </tr>
</table>

@if(count($data['expenses_by_category'] ?? []))
<h2>المصروفات حسب الفئة</h2>
<table class="summary">
    @foreach($data['expenses_by_category'] as $category => $total)
    <tr>
        <td class="label">{{ \App\Support\ReportLabels::category($category) }}</td>
        <td class="value">{{ $currency }} {{ number_format($total, 2) }}</td>
    </tr>
    @endforeach
</table>
@endif

@if(count($data['details'] ?? []))
<h2>تفاصيل الدخل</h2>
<table>
    <thead>
        <tr>
            <th>المبنى</th>
            <th>الوحدة</th>
            <th>المستأجر</th>
            <th>الإيجار</th>
            <th>المرافق</th>
            <th>المجموع</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['details'] as $row)
        <tr>
            <td>{{ $row['building'] }}</td>
            <td>{{ $row['unit'] }}</td>
            <td>{{ $row['tenant'] }}</td>
            <td>{{ $currency }} {{ number_format($row['rent'], 2) }}</td>
            <td>{{ $currency }} {{ number_format($row['utilities'], 2) }}</td>
            <td>{{ $currency }} {{ number_format($row['total'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
