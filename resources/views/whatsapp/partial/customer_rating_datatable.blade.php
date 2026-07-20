@php
    $stars = ["★☆☆☆", "★★☆☆", "★★★☆", "★★★★☆", "★★★★"];
@endphp
@foreach ($customerRatings as $key => $row)
    <tr>
        <td>{{ dateFormat($row->sent_at, 'd-m-Y H:i') }}</td>
        <td>{{ $row->customer_name }}</td>
        <td>{{ $row->rating_comment }}</td>
        <td><span class="badge bg-warning">{{ $stars[$row->rating_score-1] ?? '' }}</span></td>  
        <td>
            @if ($row->sentiment === 'positive')
                <span class="badge bg-success">{{ ucfirst($row->sentiment) }}</span>
            @elseif ($row->sentiment === 'neutral')
                <span class="badge bg-warning">{{ ucfirst($row->sentiment) }}</span>
            @elseif ($row->sentiment === 'negative')
                <span class="badge bg-danger">{{ ucfirst($row->sentiment) }}</span>
            @endif  
        </td>          
        <td>{{ ucfirst(str_replace("_", " ", $row->rating_status)) }}</td>
    </tr>
@endforeach