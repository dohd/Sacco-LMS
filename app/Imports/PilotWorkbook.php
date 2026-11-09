<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/** Empty import adapter used to read pilot workbooks with Excel::toArray(). */
class PilotWorkbook implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Excel::toArray() reads the workbook; validation is handled by the pilot service.
    }
}
