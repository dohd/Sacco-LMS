@if ($errorMsg)
    <tr>
        <td colspan="100%" class="text-center text-danger">{{ $errorMsg }}</td>
    </tr>
@else
    @foreach ($logs as $i => $log)
        <tr>
            <td>{{ $log['date'] }}</td>
            <td>{{ $log['from'] }}</td>
            <td>{{ $log['to'] }}</td>
            <td>{{ $log['body'] }}</td>
            <td>
                <span class="badge {{ in_array($log['status'], ['read', 'received', 'delivered'])? 'bg-success' : 'bg-danger' }}">
                    {{ ucfirst($log['status']) }}
                </span>
            </td>
        </tr>
    @endforeach
@endif
