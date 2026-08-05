<div class="report-header">
    <h1>كشف حساب المستأجر</h1>
    <div class="text-muted report-meta">تاريخ التقرير: {{ \Carbon\Carbon::now()->format('Y-m-d H:i') }}</div>
</div>

<h2>بيانات المستأجر</h2>
<table class="summary">
    <tr><td class="label">الاسم</td><td class="value">{{ $statement['tenant']['name'] }}</td><td class="label">عدد العقود</td><td class="value">{{ $statement['tenant']['contracts_count'] }}</td></tr>
    <tr><td class="label">الهاتف</td><td class="value">{{ $statement['tenant']['phone'] ?: '—' }}</td><td class="label">رقم الهوية</td><td class="value">{{ $statement['tenant']['id_number'] ?: '—' }}</td></tr>
    <tr><td class="label">البريد</td><td class="value">{{ $statement['tenant']['email'] ?: '—' }}</td><td class="label">العنوان</td><td class="value">{{ $statement['tenant']['address'] ?: '—' }}</td></tr>
</table>

<h2>الإجماليات</h2>
<table class="summary">
    <tr><td class="label">إجمالي المفوتر</td><td class="value">{{ $currency }} {{ number_format($statement['totals']['invoiced'], 2) }}</td>
        <td class="label">إجمالي المدفوع</td><td class="value">{{ $currency }} {{ number_format($statement['totals']['paid'], 2) }}</td></tr>
    <tr class="highlight"><td class="label">الرصيد المستحق</td><td class="value">{{ $currency }} {{ number_format($statement['totals']['balance'], 2) }}</td><td></td><td></td></tr>
</table>

@if(count($statement['invoices']))
<h2>تفاصيل الفواتير</h2>
<table>
    <thead>
        <tr><th>#</th><th>رقم الفاتورة</th><th>المبنى</th><th>الوحدة</th><th>الإصدار</th><th>الاستحقاق</th><th>الإجمالي</th><th>المدفوع</th><th>الرصيد</th><th>الحالة</th></tr>
    </thead>
    <tbody>
        @foreach($statement['invoices'] as $inv)
        <tr>
            <td>{{ $loop->index + 1 }}</td>
            <td>{{ $inv['invoice_number'] }}</td>
            <td>{{ $inv['building'] }}</td>
            <td>{{ $inv['unit'] }}</td>
            <td>{{ $inv['issue_date'] }}</td>
            <td>{{ $inv['due_date'] }}</td>
            <td>{{ $currency }} {{ number_format($inv['total'], 2) }}</td>
            <td>{{ $currency }} {{ number_format($inv['paid'], 2) }}</td>
            <td>{{ $currency }} {{ number_format($inv['balance'], 2) }}</td>
            <td>{{ $inv['status'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p class="text-muted">لا توجد فواتير مسجلة لهذا المستأجر.</p>
@endif

@if(count($statement['payments']))
<h2>سجل الدفعات</h2>
<table>
    <thead>
        <tr><th>#</th><th>رقم الإيصال</th><th>رقم الفاتورة</th><th>المبنى</th><th>الوحدة</th><th>التاريخ</th><th>طريقة الدفع</th><th>المبلغ</th></tr>
    </thead>
    <tbody>
        @foreach($statement['payments'] as $pay)
        <tr>
            <td>{{ $loop->index + 1 }}</td>
            <td>{{ $pay['receipt_number'] }}</td>
            <td>{{ $pay['invoice_number'] }}</td>
            <td>{{ $pay['building'] }}</td>
            <td>{{ $pay['unit'] }}</td>
            <td>{{ $pay['payment_date'] }}</td>
            <td>{{ $pay['method'] }}</td>
            <td>{{ $currency }} {{ number_format($pay['amount'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p class="text-muted">لا توجد دفعات مسجلة لهذا المستأجر.</p>
@endif