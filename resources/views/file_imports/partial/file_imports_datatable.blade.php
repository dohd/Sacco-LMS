@foreach ($file_imports as $i => $file)
<tr>
    <th scope="row">{{ $i+1 }}</th>
    <td>{{ $file->origin_name }}</td>
    <td>{{ $file->category }}</td>
    <td>{{ dateFormat($file->start_date) }}</td>
    <td>{!! $file->action_buttons !!}</td>
</tr>
@endforeach