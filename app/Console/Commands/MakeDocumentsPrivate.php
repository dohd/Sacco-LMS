<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MakeDocumentsPrivate extends Command
{
    protected $signature = 'documents:make-private';

    protected $description = 'Move already-uploaded member, loan and nomination documents from the public disk to the private disk';

    private const COLUMNS = [
        'member_applications' => ['national_id_front', 'national_id_back', 'passport_photo_1', 'passport_photo_2', 'nominee_form', 'applicant_signature'],
        'loan_applications' => ['applicant_signature'],
        'loan_guarantors' => ['signature'],
        'loan_securities' => ['supporting_document'],
        'loan_witnesses' => ['signature'],
        'nominations' => ['member_signature'],
        'nominee_witnesses' => ['signature'],
    ];

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('private');
        $moved = $skipped = 0;

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $paths = DB::table($table)->whereNotNull($column)->where($column, '!=', '')->pluck($column);

                foreach ($paths->unique() as $path) {
                    if (!$public->exists($path)) {
                        $skipped++;
                        continue;
                    }

                    $private->put($path, $public->get($path));
                    $public->delete($path);
                    $moved++;
                }
            }
        }

        $this->info("Moved {$moved} file(s) to the private disk; {$skipped} path(s) had no public file.");

        return 0;
    }
}
