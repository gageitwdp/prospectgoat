<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class LeadImportController extends Controller
{
    public function __construct()
    {
        abort_if($this->currentUserIsGlobalAdmin(), 403);
    }

    private const TEMPLATE_COLUMNS = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'inquiry_type',
    ];

    public function index(): View
    {
        return view('admin.imports.leads.index');
    }

    public function downloadTemplate()
    {
        $header = implode(',', self::TEMPLATE_COLUMNS);
        $example = implode(',', [
            'Jane',
            'Prospect',
            'jane.prospect@yourdomain.com',
            '555-0100',
            'buyer',
        ]);

        $csv = $header."\n".$example."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="lead-import-template.csv"',
        ]);
    }

    public function export(): Response
    {
        $columns = [
            'id',
            'name',
            'email',
            'phone',
            'address',
            'lead_type',
            'source',
            'status',
            'assigned_email',
            'created_at',
            'updated_at',
        ];

        $lines = [implode(',', $columns)];

        $leads = Lead::query()
            ->where('account_id', $this->requireCurrentAccountId())
            ->with('assignedManager')
            ->orderBy('id')
            ->get();

        foreach ($leads as $lead) {
            $lines[] = $this->toCsvRow([
                $lead->id,
                $lead->name,
                $lead->email,
                $lead->phone,
                $lead->address,
                $lead->lead_type,
                $lead->source,
                $lead->status,
                $lead->assignedManager?->email,
                $lead->created_at?->toDateTimeString(),
                $lead->updated_at?->toDateTimeString(),
            ]);
        }

        $csv = implode("\n", $lines)."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="lead-export.csv"',
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $accountId = $this->requireCurrentAccountId();

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');

        if (! $handle) {
            return redirect()
                ->route('admin.imports.leads.index')
                ->with('status', 'Unable to read uploaded CSV file.');
        }

        $headerRow = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($value) => $this->normalizeHeader($value), $headerRow);

        $missing = array_diff(self::TEMPLATE_COLUMNS, $headers);

        if (! empty($missing)) {
            fclose($handle);

            return redirect()
                ->route('admin.imports.leads.index')
                ->with('status', 'Import failed. CSV is missing required columns: '.implode(', ', $missing));
        }

        $headerIndex = array_flip($headers);

        $createdCount = 0;
        $skippedCount = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $rowData = [];
            foreach (self::TEMPLATE_COLUMNS as $column) {
                $rowData[$column] = trim((string) ($row[$headerIndex[$column]] ?? ''));
            }

            $rowData['inquiry_type'] = $this->normalizeInquiryType($rowData['inquiry_type']);
            $rowData['email'] = $rowData['email'] !== '' ? $rowData['email'] : null;
            $rowData['phone'] = $rowData['phone'] !== '' ? $rowData['phone'] : null;
            $rowData['inquiry_type'] = $rowData['inquiry_type'] !== '' ? $rowData['inquiry_type'] : null;

            $validator = Validator::make($rowData, [
                'first_name' => ['required', 'string', 'max:120'],
                'last_name' => ['required', 'string', 'max:120'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
                'inquiry_type' => ['nullable', 'in:home_value,buyer,seller,generic_inquiry'],
            ]);

            if ($validator->fails()) {
                $skippedCount++;
                $errors[] = 'Line '.$line.': '.$validator->errors()->first();
                continue;
            }

            // Import should not trigger lead-created events that may send confirmation emails.
            $lead = Lead::withoutEvents(function () use ($rowData, $accountId, $request): Lead {
                return Lead::create([
                    'account_id' => $accountId,
                    'name' => trim($rowData['first_name'].' '.$rowData['last_name']),
                    'email' => $rowData['email'] ?? null,
                    'phone' => $rowData['phone'] ?? null,
                    'address' => null,
                    'lead_type' => $rowData['inquiry_type'],
                    'source' => 'homepage',
                    'status' => 'new',
                    'assigned_to' => null,
                    'created_by' => $request->user()?->id,
                ]);
            });

            $lead->activities()->create([
                'account_id' => $accountId,
                'type' => 'note',
                'description' => 'Lead imported from admin CSV upload.',
            ]);

            $createdCount++;
        }

        fclose($handle);

        $status = sprintf('Import complete. %d created, %d skipped.', $createdCount, $skippedCount);

        return redirect()
            ->route('admin.imports.leads.index')
            ->with('status', $status)
            ->with('import_errors', array_slice($errors, 0, 10));
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(trim($header));
    }

    private function normalizeInquiryType(string $inquiryType): string
    {
        $normalized = strtolower(trim($inquiryType));

        return match ($normalized) {
            'home value', 'home_value' => 'home_value',
            'buyer' => 'buyer',
            'seller' => 'seller',
            'generic inquiry', 'generic_inquiry', 'generic' => 'generic_inquiry',
            default => $normalized,
        };
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function toCsvRow(array $values): string
    {
        $escaped = array_map(function ($value): string {
            $string = (string) ($value ?? '');
            $string = str_replace('"', '""', $string);

            return '"'.$string.'"';
        }, $values);

        return implode(',', $escaped);
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
