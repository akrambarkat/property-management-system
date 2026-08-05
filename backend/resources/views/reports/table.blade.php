<div class="report-header">
    <h1>{{ $title }}</h1>
    <div class="text-muted report-meta">تاريخ التقرير: {{ \Carbon\Carbon::now()->format('Y-m-d H:i') }}</div>
</div>

@if(count($rows))
<table>
    <thead>
        <tr>
            @foreach($columns as $col)
            <th>{{ $col['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            @for($i = 0; $i < count($columns); $i++)
            <td>
                @php
                    $key = $columns[$i]['key'];
                    $value = is_array($row) ? ($row[$key] ?? ($row[$i] ?? '')) : ($row->$key ?? '');
                @endphp
                {{ $value }}
            </td>
            @endfor
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p class="text-muted">لا توجد بيانات لعرضها ضمن هذه الفترة.</p>
@endif
