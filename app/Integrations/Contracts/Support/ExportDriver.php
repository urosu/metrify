<?php

namespace App\Integrations\Contracts\Support;

/**
 * Contract for data export drivers.
 *
 * Implementations: CsvExportDriver, ExcelExportDriver, PdfExportDriver.
 *
 * Used by ExportJob (queued via POST /{workspace}/api/export).
 * CSV and Excel use spatie/simple-excel (OpenSpout) for streaming with ~3MB memory.
 * PDF uses Gotenberg (Docker sidecar on port 3000).
 *
 * Row limit: 100K per export.
 */
interface ExportDriver
{
    /**
     * Get the file format identifier.
     *
     * @return 'csv'|'xlsx'|'pdf'
     */
    public function getFormat(): string;

    /**
     * Get the MIME type for the exported file.
     *
     * @return string  e.g., 'text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
     */
    public function getMimeType(): string;

    /**
     * Get the file extension (without dot).
     *
     * @return string  e.g., 'csv', 'xlsx', 'pdf'
     */
    public function getExtension(): string;

    /**
     * Export the given data to a file and return the local path.
     *
     * Implementations must stream rows to keep memory under ~3MB.
     * The caller handles uploading to S3 and generating a signed URL.
     *
     * @param  iterable<array<string, mixed>>  $rows     Data rows as associative arrays.
     * @param  list<string>                    $columns  Column headers in display order.
     * @param  array{title?: string, workspace_name?: string, date_range?: string}  $meta
     *         Optional metadata for PDF headers / Excel sheet names.
     * @return string  Absolute path to the generated file in temporary storage.
     *
     * @throws \App\Exceptions\ExportException
     */
    public function export(iterable $rows, array $columns, array $meta = []): string;
}
