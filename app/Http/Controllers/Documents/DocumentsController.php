<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Memberships\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Streams uploaded documents (IDs, signatures, collateral papers) from the private disk.
 *
 * A file is only served if its path is recorded against a known record and the
 * user is staff (view-members / administrator) or one of the members the record belongs to.
 */
class DocumentsController extends Controller
{
    public const DISK = 'private';

    /**
     * Where document paths are stored: table => [columns, resolver of the owning member ids].
     */
    private function sources(): array
    {
        return [
            'member_applications' => [
                ['national_id_front', 'national_id_back', 'passport_photo_1', 'passport_photo_2', 'nominee_form', 'applicant_signature'],
                fn ($row) => Member::where('member_application_id', $row->id)->pluck('id')->all(),
            ],
            'loan_applications' => [
                ['applicant_signature'],
                fn ($row) => [$row->member_id],
            ],
            'loan_guarantors' => [
                ['signature'],
                fn ($row) => array_filter([$row->member_id, DB::table('loan_applications')->where('id', $row->loan_application_id)->value('member_id')]),
            ],
            'loan_securities' => [
                ['supporting_document'],
                fn ($row) => array_filter([$row->member_id, DB::table('loan_applications')->where('id', $row->loan_application_id)->value('member_id')]),
            ],
            'loan_witnesses' => [
                ['signature'],
                fn ($row) => array_filter([DB::table('loan_applications')->where('id', $row->loan_application_id)->value('member_id')]),
            ],
            'nominations' => [
                ['member_signature'],
                fn ($row) => [$row->member_id],
            ],
            'nominee_witnesses' => [
                ['signature'],
                fn ($row) => array_filter([DB::table('nominations')->where('id', $row->nomination_id)->value('member_id')]),
            ],
        ];
    }

    public function show(Request $request)
    {
        $path = (string) $request->query('path', '');

        // Reject traversal / absolute paths before touching storage or the database.
        abort_if($path === '' || strpos($path, "\0") !== false || strpos($path, '..') !== false
            || $path[0] === '/' || $path[0] === '\\' || preg_match('#^[A-Za-z]:#', $path), 404);

        $memberIds = $this->owningMemberIds($path);
        abort_if($memberIds === null, 404);

        $isStaff = $this->hasSaccoPermission('view-members');
        abort_unless($isStaff || $this->ownsAny($memberIds), 403);

        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Member ids allowed to view the path, or null if no record references it.
     */
    private function owningMemberIds(string $path): ?array
    {
        $found = false;
        $ids = [];

        foreach ($this->sources() as $table => [$columns, $resolver]) {
            $query = DB::table($table)->where(function ($q) use ($columns, $path) {
                foreach ($columns as $column) {
                    $q->orWhere($column, $path);
                }
            });

            foreach ($query->get() as $row) {
                $found = true;
                $ids = array_merge($ids, $resolver($row));
            }
        }

        return $found ? array_values(array_unique($ids)) : null;
    }

    private function ownsAny(array $memberIds): bool
    {
        return $memberIds !== [] && auth()->check()
            && Member::whereIn('id', $memberIds)->where('user_id', auth()->id())->exists();
    }
}
