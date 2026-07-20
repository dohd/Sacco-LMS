<?php

namespace App\Imports;

use App\Models\employee\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class EmployeeImport implements ToCollection, WithBatchInserts, WithChunkReading
{
    public function collection(Collection $rows)
    {
        $rows = $rows->toArray();

        // check and remove header row
        $keys = ['Payroll No.', 'ID No.', 'Tax Pin', 'Surname', 'First Name', 'Other Name'];
        if (array_intersect($rows[0], $keys)) {
            $rows = array_slice($rows, 1);
        }

        $result = DB::transaction(function() use($rows) {
            $employeeCols = DB::table('information_schema.columns')
            ->select('COLUMN_NAME')
            ->where('table_schema', DB::getDatabaseName()) // current DB
            ->where('table_name', 'employees')
            ->whereNotIn('COLUMN_NAME', ['id', 'ins', 'user_id', 'created_at', 'updated_at'])
            ->orderBy('ORDINAL_POSITION')
            ->pluck('COLUMN_NAME')
            ->toArray();

            foreach ($rows as $i => $row) {
                $rowValues = array_slice($row, 0, count($employeeCols));
                $data = array_combine($employeeCols, $rowValues);
                $data['gross_salary'] = numberClean($data['gross_salary']);
                Employee::create($data);
            }
            return true;
        });
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
