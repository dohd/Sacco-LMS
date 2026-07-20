@foreach ($invoices as $i => $row)
    <tr>
        <th scope="row">{{ $i+1 }}</th>
        <td>{{ dateFormat($row->date, 'd M Y') }}</td>
        <td>{{ $row->zoho_invoice_number }}</td>
        <td>{{ $row->customer_name  }}</td>
        <td>
            @if ($row->zoho_status == 'draft')
                <span class="badge bg-warning status-btn" style="cursor: pointer;" data-id="{{$row->id}}" data-bs-toggle="modal" data-bs-target="#statusModal">
                    draft<i class="bi bi-caret-down-fill"></i>
                </span>
            @else
                <span class="badge bg-success">{{ $row->zoho_status }}</span>
            @endif
        </td>
        <td>{{ dateFormat($row->due_date, 'd M Y') }}</td>
        <td>{{ numberFormat($row->total)  }}</td>
        <td>{!! $row->action_buttons !!}</td>
    </tr>
@endforeach