<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ProspectingController extends Controller
{
    private const DEFAULT_PHONE = '111-111-1111';

    private const DEFAULT_EMAIL = 'default@lezinproperties.com';

    /**
     * @var array<string, string>
     */
    private const REQUIRED_COLUMNS = [
        'owner 1 full' => 'owner_full_name',
        'property full address' => 'property_full_address',
        'property address' => 'property_address',
        'property city' => 'property_city',
        'property state' => 'property_state',
        'property zip' => 'property_zip',
    ];

    public function index(): View
    {
        return view('admin.prospecting.index');
    }

    public function parseCsv(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please upload a valid CSV file.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return response()->json([
                'message' => 'Unable to read uploaded CSV file.',
            ], 422);
        }

        $headerRow = fgetcsv($handle) ?: [];
        $headers = array_map(fn (string $value): string => $this->normalizeHeader($value), $headerRow);
        $missing = array_diff(array_keys(self::REQUIRED_COLUMNS), $headers);

        if (! empty($missing)) {
            fclose($handle);

            return response()->json([
                'message' => 'CSV is missing required columns: '.implode(', ', $missing),
            ], 422);
        }

        $headerIndex = array_flip($headers);
        $rows = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $mapped = ['line' => $line];

            foreach (self::REQUIRED_COLUMNS as $column => $key) {
                $mapped[$key] = trim((string) ($row[$headerIndex[$column]] ?? ''));
            }

            $rows[] = $mapped;
        }

        fclose($handle);

        return response()->json([
            'message' => sprintf('%d prospect row(s) loaded.', count($rows)),
            'count' => count($rows),
            'rows' => $rows,
        ]);
    }

    public function storeLead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'owner_full_name' => ['required', 'string', 'max:255'],
            'property_full_address' => ['required', 'string', 'max:255'],
            'property_address' => ['nullable', 'string', 'max:255'],
            'property_city' => ['nullable', 'string', 'max:120'],
            'property_state' => ['nullable', 'string', 'max:20'],
            'property_zip' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please fix the highlighted fields and try again.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $name = trim($data['owner_full_name']);
        $address = trim($data['property_full_address']);

        if ($this->duplicateLeadExists($name, $address)) {
            return response()->json([
                'message' => 'This lead already exists and was skipped.',
            ], 409);
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        $lead = Lead::create([
            'name' => $name,
            'email' => $email !== '' ? $email : self::DEFAULT_EMAIL,
            'phone' => $phone !== '' ? $phone : self::DEFAULT_PHONE,
            'address' => $address,
            'lead_type' => 'generic_inquiry',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $lead->activities()->create([
            'type' => 'note',
            'description' => 'Lead saved from admin prospecting tool CSV workflow.',
        ]);

        return response()->json([
            'message' => 'Lead saved successfully.',
            'lead_id' => $lead->id,
        ], 201);
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(trim($header));
    }

    private function duplicateLeadExists(string $name, string $address): bool
    {
        $normalizedName = strtolower(trim($name));
        $normalizedAddress = strtolower(trim($address));

        return Lead::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
            ->whereRaw('LOWER(TRIM(address)) = ?', [$normalizedAddress])
            ->exists();
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
