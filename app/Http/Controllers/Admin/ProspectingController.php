<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\ProspectingSession;
use App\Services\Prospecting\ProspectingScriptLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ProspectingController extends Controller
{
    public function __construct(private readonly ProspectingScriptLibraryService $scriptLibrary)
    {
        abort_if($this->currentUserIsGlobalAdmin(), 403);
    }

    private const DEFAULT_PHONE = '111-111-1111';

    private const DEFAULT_EMAIL = 'default@prospectgoat.com';

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
        $accountId = $this->requireCurrentAccountId();
        $userId = auth()->id();

        $session = null;

        if (is_numeric($userId) && (int) $userId > 0) {
            $session = ProspectingSession::query()
                ->where('account_id', $accountId)
                ->where('user_id', (int) $userId)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();
        }

        return view('admin.prospecting.index', [
            'scripts' => $this->scriptLibrary->scriptsForProspectingTool($accountId),
            'prospectingSession' => $session ? [
                'csv_filename' => $session->csv_filename,
                'state' => $session->state,
            ] : null,
        ]);
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

        $accountId = $this->requireCurrentAccountId();
        $userId = (int) ($request->user()?->id ?? 0);

        if ($userId > 0) {
            $this->upsertProspectingSession(
                $accountId,
                $userId,
                [
                    'rows' => $rows,
                    'current_index' => 0,
                    'edits' => [],
                    'saved_rows' => [],
                    'script_phone' => '',
                ],
                (string) ($file?->getClientOriginalName() ?? ''),
            );
        }

        return response()->json([
            'message' => sprintf('%d prospect row(s) loaded.', count($rows)),
            'count' => count($rows),
            'rows' => $rows,
        ]);
    }

    public function updateSessionState(Request $request): JsonResponse
    {
        $accountId = $this->requireCurrentAccountId();
        $userId = (int) ($request->user()?->id ?? 0);

        if ($userId <= 0) {
            return response()->json([
                'message' => 'Unable to resolve user context.',
            ], 422);
        }

        $data = $request->validate([
            'csv_filename' => ['nullable', 'string', 'max:255'],
            'rows' => ['sometimes', 'array'],
            'current_index' => ['required', 'integer', 'min:0'],
            'edits' => ['sometimes', 'array'],
            'saved_rows' => ['sometimes', 'array'],
            'script_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $existingSession = ProspectingSession::query()
            ->where('account_id', $accountId)
            ->where('user_id', $userId)
            ->first();

        $existingState = is_array($existingSession?->state) ? $existingSession->state : [];

        $rows = array_values($data['rows'] ?? (is_array($existingState['rows'] ?? null) ? $existingState['rows'] : []));
        $maxIndex = max(0, count($rows) - 1);
        $currentIndex = count($rows) === 0 ? 0 : min((int) $data['current_index'], $maxIndex);
        $edits = $data['edits'] ?? (is_array($existingState['edits'] ?? null) ? $existingState['edits'] : []);
        $savedRows = $data['saved_rows'] ?? (is_array($existingState['saved_rows'] ?? null) ? $existingState['saved_rows'] : []);
        $scriptPhone = array_key_exists('script_phone', $data)
            ? trim((string) ($data['script_phone'] ?? ''))
            : trim((string) ($existingState['script_phone'] ?? ''));
        $csvFilename = array_key_exists('csv_filename', $data)
            ? (string) ($data['csv_filename'] ?? '')
            : (string) ($existingSession?->csv_filename ?? '');

        $this->upsertProspectingSession(
            $accountId,
            $userId,
            [
                'rows' => $rows,
                'current_index' => $currentIndex,
                'edits' => $edits,
                'saved_rows' => $savedRows,
                'script_phone' => $scriptPhone,
            ],
            $csvFilename,
        );

        return response()->json([
            'message' => 'Prospecting session state saved.',
        ]);
    }

    public function storeLead(Request $request): JsonResponse
    {
        abort_if($this->currentUserIsGlobalAdmin(), 403);

        $accountId = $this->requireCurrentAccountId();

        $validator = Validator::make($request->all(), [
            'owner_full_name' => ['required', 'string', 'max:255'],
            'owner_2_full_name' => ['nullable', 'string', 'max:255'],
            'property_full_address' => ['required', 'string', 'max:255'],
            'property_address' => ['nullable', 'string', 'max:255'],
            'property_city' => ['nullable', 'string', 'max:120'],
            'property_state' => ['nullable', 'string', 'max:20'],
            'property_zip' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'owner_2_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'owner_2_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
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

        if ($this->duplicateLeadExists($name, $address, $accountId)) {
            return response()->json([
                'message' => 'This lead already exists and was skipped.',
            ], 409);
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $owner2FullName = trim((string) ($data['owner_2_full_name'] ?? ''));
        $owner2Phone = trim((string) ($data['owner_2_phone'] ?? ''));
        $owner2Email = trim((string) ($data['owner_2_email'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));

        $lead = Lead::create([
            'account_id' => $accountId,
            'name' => $name,
            'owner_2_full_name' => $owner2FullName !== '' ? $owner2FullName : null,
            'email' => $email !== '' ? $email : self::DEFAULT_EMAIL,
            'owner_2_email' => $owner2Email !== '' ? $owner2Email : null,
            'phone' => $phone !== '' ? $phone : self::DEFAULT_PHONE,
            'owner_2_phone' => $owner2Phone !== '' ? $owner2Phone : null,
            'address' => $address,
            'prospecting_notes' => $notes !== '' ? $notes : null,
            'lead_type' => 'generic_inquiry',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
            'created_by' => $request->user()?->id,
        ]);

        $lead->activities()->create([
            'account_id' => $accountId,
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

    private function duplicateLeadExists(string $name, string $address, int $accountId): bool
    {
        $normalizedName = strtolower(trim($name));
        $normalizedAddress = strtolower(trim($address));

        return Lead::query()
            ->where('account_id', $accountId)
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

    /**
     * @param  array<string, mixed>  $state
     */
    private function upsertProspectingSession(int $accountId, int $userId, array $state, string $csvFilename): void
    {
        $session = ProspectingSession::query()->updateOrCreate([
            'account_id' => $accountId,
            'user_id' => $userId,
        ], [
            'csv_filename' => trim($csvFilename) !== '' ? trim($csvFilename) : null,
            'state' => $state,
        ]);
    }
}
