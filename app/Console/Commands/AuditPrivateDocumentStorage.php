<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\PrivateDocumentStorageService;
use Illuminate\Console\Command;

class AuditPrivateDocumentStorage extends Command
{
    protected $signature = 'smartfind:audit-storage
                            {--details : Tampilkan semua record termasuk yang PASS}';

    protected $description =
        'Audit read-only file_path SmartFind agar tetap relative, valid, dan berada di dalam private storage root.';


    public function handle(
        PrivateDocumentStorageService $privateStorage
    ): int {

        $showDetails =
            (bool) $this->option(
                'details'
            );

        $rows = [];
        $passed = 0;
        $failed = 0;


        /*
        |--------------------------------------------------------------------------
        | Current Documents
        |--------------------------------------------------------------------------
        */

        Document::query()
            ->select([
                'id',
                'title',
                'file_path',
                'file_name',
                'is_active',
            ])
            ->orderBy('id')
            ->chunkById(
                200,
                function ($documents) use (
                    $privateStorage,
                    $showDetails,
                    &$rows,
                    &$passed,
                    &$failed
                ) {

                    foreach (
                        $documents
                        as $document
                    ) {
                        $result =
                            $this->auditPath(
                                privateStorage:
                                    $privateStorage,

                                source:
                                    'document',

                                sourceId:
                                    (int) $document->id,

                                label:
                                    (string) $document->title,

                                filePath:
                                    (string) $document->file_path,

                                fileName:
                                    (string) $document->file_name
                            );

                        if (
                            $result[
                                'status'
                            ] === 'PASS'
                        ) {
                            $passed++;
                        } else {
                            $failed++;
                        }

                        if (
                            $showDetails
                            || $result[
                                'status'
                            ] !== 'PASS'
                        ) {
                            $rows[] =
                                $result;
                        }
                    }
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Document Versions
        |--------------------------------------------------------------------------
        */

        DocumentVersion::query()
            ->select([
                'id',
                'document_id',
                'revision_number',
                'file_path',
                'file_name',
            ])
            ->orderBy('id')
            ->chunkById(
                200,
                function ($versions) use (
                    $privateStorage,
                    $showDetails,
                    &$rows,
                    &$passed,
                    &$failed
                ) {

                    foreach (
                        $versions
                        as $version
                    ) {
                        $result =
                            $this->auditPath(
                                privateStorage:
                                    $privateStorage,

                                source:
                                    'version',

                                sourceId:
                                    (int) $version->id,

                                label:
                                    'Document '
                                    . (int) $version->document_id
                                    . ' / REV '
                                    . (int) $version->revision_number,

                                filePath:
                                    (string) $version->file_path,

                                fileName:
                                    (string) $version->file_name
                            );

                        if (
                            $result[
                                'status'
                            ] === 'PASS'
                        ) {
                            $passed++;
                        } else {
                            $failed++;
                        }

                        if (
                            $showDetails
                            || $result[
                                'status'
                            ] !== 'PASS'
                        ) {
                            $rows[] =
                                $result;
                        }
                    }
                }
            );


        $total =
            $passed
            + $failed;

        $this->newLine();

        $this->line(
            $failed === 0
                ? '<fg=green;options=bold>PRIVATE STORAGE AUDIT: PASS</>'
                : '<fg=red;options=bold>PRIVATE STORAGE AUDIT: FAIL</>'
        );

        $this->newLine();

        $this->table(
            [
                'TOTAL',
                'PASS',
                'FAIL',
            ],
            [[
                $total,
                $passed,
                $failed,
            ]]
        );

        if (! empty($rows)) {

            $this->newLine();

            $this->table(
                [
                    'Status',
                    'Source',
                    'ID',
                    'Label',
                    'Relative Path',
                    'Exists/Inside Root',
                ],
                array_map(
                    fn (array $row) => [
                        $row['status'],
                        $row['source'],
                        $row['source_id'],
                        $row['label'],
                        $row['relative_path'],
                        $row['resolved']
                            ? 'YES'
                            : 'NO',
                    ],
                    $rows
                )
            );

        } elseif ($failed === 0) {

            $this->info(
                'Semua document dan revision path aman. Gunakan --details untuk melihat seluruh record.'
            );
        }

        return $failed === 0
            ? self::SUCCESS
            : self::FAILURE;
    }


    private function auditPath(
        PrivateDocumentStorageService $privateStorage,
        string $source,
        int $sourceId,
        string $label,
        string $filePath,
        string $fileName
    ): array {

        $safeRelative =
            $privateStorage
                ->isSafeRelativePath(
                    $filePath
                );

        $absolutePath =
            $safeRelative
                ? $privateStorage
                    ->resolveExistingFile(
                        $filePath
                    )
                : null;

        $passed =
            $safeRelative
            && $absolutePath !== null;

        return [
            'status' =>
                $passed
                    ? 'PASS'
                    : 'FAIL',

            'source' =>
                $source,

            'source_id' =>
                $sourceId,

            'label' =>
                $label !== ''
                    ? $label
                    : $fileName,

            'relative_path' =>
                $filePath !== ''
                    ? $filePath
                    : '[EMPTY]',

            'resolved' =>
                $absolutePath !== null,
        ];
    }
}
