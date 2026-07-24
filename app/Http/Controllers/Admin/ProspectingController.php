<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\ProspectingScript;
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

    private const CSV_PROFILE_MAILING_LABELS_8 = 'mailing_labels_8';

    /**
     * Canonical export headers for the "Mailing Labels (8)" CSV profile.
     *
     * @var array<int, string>
     */
    private const MAILING_LABELS_8_HEADERS = [
        'Owner 1 Full',
        'Owner 1 First',
        'Owner 1 Middle',
        'Owner 1 Last',
        'Owner 2 Full',
        'Owner 2 First',
        'Owner 2 Middle',
        'Owner 2 Last',
        'Property Full Address',
        'Property Address',
        'Property City',
        'Property State',
        'Property ZIP',
        'Property ZIP plus 4',
        'Tax Full Address',
        'Tax Address',
        'Tax City',
        'Tax State',
        'Tax ZIP',
        'Tax ZIP plus 4',
        'On Do Not Mail List',
    ];

    /**
     * @var array<string, array{required: bool, aliases: array<int, string>}>
     */
    private const CSV_COLUMN_SCHEMA = [
        'owner_full_name' => [
            'required' => true,
            'aliases' => [
                'owner 1 full',
                'owner 1 full name',
                'owner 1',
                'owner full',
                'owner full name',
                'owner name',
                'primary owner',
                'primary owner name',
                'name',
            ],
        ],
        'property_full_address' => [
            'required' => true,
            'aliases' => [
                'property full address',
                'full property address',
                'property address full',
                'property full addr',
                'full address',
                'address',
                'mailing address',
                'street address',
            ],
        ],
        'property_address' => [
            'required' => false,
            'aliases' => [
                'property address',
                'address line 1',
                'street',
                'property street',
            ],
        ],
        'property_city' => [
            'required' => false,
            'aliases' => [
                'property city',
                'city',
                'property town',
            ],
        ],
        'property_state' => [
            'required' => false,
            'aliases' => [
                'property state',
                'state',
                'province',
                'region',
            ],
        ],
        'property_zip' => [
            'required' => false,
            'aliases' => [
                'property zip',
                'property zipcode',
                'zip',
                'zip code',
                'zipcode',
                'postal code',
                'postcode',
            ],
        ],
        'phone' => [
            'required' => false,
            'aliases' => [
                'phone',
                'phone number',
                'owner phone',
                'primary phone',
                'cell',
                'mobile',
            ],
        ],
        'owner_2_full_name' => [
            'required' => false,
            'aliases' => [
                'owner 2 full',
                'owner 2 full name',
                'owner 2',
                'secondary owner',
                'secondary owner name',
                'co owner',
                'co-owner',
            ],
        ],
        'owner_2_phone' => [
            'required' => false,
            'aliases' => [
                'owner 2 phone',
                'owner2 phone',
                'secondary phone',
                'co owner phone',
            ],
        ],
        'email' => [
            'required' => false,
            'aliases' => [
                'email',
                'email address',
                'owner email',
                'primary email',
            ],
        ],
        'owner_2_email' => [
            'required' => false,
            'aliases' => [
                'owner 2 email',
                'owner2 email',
                'secondary email',
                'co owner email',
            ],
        ],
        'notes' => [
            'required' => false,
            'aliases' => [
                'notes',
                'note',
                'comments',
                'comment',
                'prospecting notes',
            ],
        ],
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
            'scripts' => $this->scriptLibrary->scriptsForProspectingTool($accountId, is_numeric($userId) ? (int) $userId : null),
            'prospectingSession' => $session ? [
                'csv_filename' => $session->csv_filename,
                'state' => $session->state,
            ] : null,
        ]);
    }

    public function storePrivateScript(Request $request): JsonResponse
    {
        $accountId = $this->requireCurrentAccountId();
        $userId = (int) ($request->user()?->id ?? 0);

        if ($userId <= 0) {
            return response()->json([
                'message' => 'Unable to resolve user context.',
            ], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'content' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $maxSortOrder = (int) ProspectingScript::query()
            ->where('account_id', $accountId)
            ->where('user_id', $userId)
            ->max('sort_order');

        $script = ProspectingScript::query()->create([
            'account_id' => $accountId,
            'user_id' => $userId,
            'name' => trim($data['name']),
            'content' => $data['content'],
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $maxSortOrder + 1,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return response()->json([
            'message' => 'Private script created.',
            'script' => $this->scriptPayload($script),
        ], 201);
    }

    public function updatePrivateScript(Request $request, ProspectingScript $prospectingScript): JsonResponse
    {
        $accountId = $this->requireCurrentAccountId();
        $userId = (int) ($request->user()?->id ?? 0);

        if ($userId <= 0) {
            return response()->json([
                'message' => 'Unable to resolve user context.',
            ], 422);
        }

        abort_unless($prospectingScript->account_id === $accountId, 404);
        abort_unless($prospectingScript->user_id === $userId, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'content' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $prospectingScript->update([
            'name' => trim($data['name']),
            'content' => $data['content'],
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : (int) $prospectingScript->sort_order,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return response()->json([
            'message' => 'Private script updated.',
            'script' => $this->scriptPayload($prospectingScript->fresh()),
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

        if ($headerRow === []) {
            fclose($handle);

            return response()->json([
                'message' => 'CSV appears to be empty or missing a header row.',
            ], 422);
        }

        $headerResolution = $this->resolveCsvHeaders($headerRow);
        $headerIndex = $headerResolution['header_index'];
        $detectedProfile = $headerResolution['detected_profile'];
        $mappedFields = $headerResolution['mapped_fields'];
        $unmappedFields = $headerResolution['unmapped_fields'];
        $normalizedHeaders = $headerResolution['normalized_headers'];
        $requiredUnmapped = array_values(array_filter($unmappedFields, function (string $field): bool {
            return (bool) (self::CSV_COLUMN_SCHEMA[$field]['required'] ?? false);
        }));

        $importFields = array_keys(self::CSV_COLUMN_SCHEMA);
        $rows = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $mapped = ['line' => $line];

            foreach ($importFields as $field) {
                $index = $headerIndex[$field] ?? null;
                $mapped[$field] = is_int($index)
                    ? trim((string) ($row[$index] ?? ''))
                    : '';
            }

            $this->enrichMappedRow($mapped, $row, $normalizedHeaders);

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

        $warnings = [];

        if (! empty($requiredUnmapped)) {
            $warnings[] = 'Some required fields could not be mapped from headers and were left blank: '.implode(', ', $requiredUnmapped).'.';
        }

        if (empty($mappedFields)) {
            $warnings[] = 'No supported headers were detected. Rows were loaded as blanks for manual completion.';
        }

        return response()->json([
            'message' => sprintf('%d prospect row(s) loaded.', count($rows)),
            'count' => count($rows),
            'rows' => $rows,
            'mapping' => [
                'detected_profile' => $detectedProfile,
                'mapped_fields' => $mappedFields,
                'unmapped_fields' => $unmappedFields,
                'warnings' => $warnings,
            ],
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
        $normalized = strtolower(trim($header));
        $normalized = str_replace(['_', '-'], ' ', $normalized);
        $normalized = preg_replace('/[^a-z0-9\s]/', ' ', $normalized) ?? $normalized;

        return preg_replace('/\s+/', ' ', trim($normalized)) ?? trim($normalized);
    }

    /**
     * @param  array<int, string|null>  $headerRow
     * @return array{
     *   header_index: array<string, int>,
     *   detected_profile: string|null,
     *   mapped_fields: array<int, string>,
     *   unmapped_fields: array<int, string>,
     *   normalized_headers: array<string, int>
     * }
     */
    private function resolveCsvHeaders(array $headerRow): array
    {
        $normalizedHeaders = [];

        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader((string) ($header ?? ''));

            if ($normalized === '' || array_key_exists($normalized, $normalizedHeaders)) {
                continue;
            }

            $normalizedHeaders[$normalized] = $index;
        }

        $detectedProfile = null;
        $resolved = $this->resolveMailingLabels8HeaderIndex($normalizedHeaders);

        if ($resolved !== null) {
            $detectedProfile = self::CSV_PROFILE_MAILING_LABELS_8;
        } else {
            $resolved = $this->resolveDynamicCsvHeaderIndex($normalizedHeaders);
        }

        $mappedFields = [];
        $unmappedFields = [];

        foreach (array_keys(self::CSV_COLUMN_SCHEMA) as $field) {
            if (array_key_exists($field, $resolved)) {
                $mappedFields[] = $field;
            } else {
                $unmappedFields[] = $field;
            }
        }

        return [
            'header_index' => $resolved,
            'detected_profile' => $detectedProfile,
            'mapped_fields' => $mappedFields,
            'unmapped_fields' => $unmappedFields,
            'normalized_headers' => $normalizedHeaders,
        ];
    }

    /**
     * @param  array<string, int>  $normalizedHeaders
     * @return array<string, int>|null
     */
    private function resolveMailingLabels8HeaderIndex(array $normalizedHeaders): ?array
    {
        $profileHeaders = [];

        foreach (self::MAILING_LABELS_8_HEADERS as $header) {
            $profileHeaders[] = $this->normalizeHeader($header);
        }

        foreach ($profileHeaders as $profileHeader) {
            if (! array_key_exists($profileHeader, $normalizedHeaders)) {
                return null;
            }
        }

        $mapping = [
            'owner_full_name' => 'owner 1 full',
            'owner_2_full_name' => 'owner 2 full',
            'property_full_address' => 'property full address',
            'property_address' => 'property address',
            'property_city' => 'property city',
            'property_state' => 'property state',
            'property_zip' => 'property zip',
        ];

        $resolved = [];

        foreach ($mapping as $field => $normalizedHeader) {
            if (array_key_exists($normalizedHeader, $normalizedHeaders)) {
                $resolved[$field] = $normalizedHeaders[$normalizedHeader];
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, int>  $normalizedHeaders
     * @return array<string, int>
     */
    private function resolveDynamicCsvHeaderIndex(array $normalizedHeaders): array
    {
        $resolved = [];

        foreach (self::CSV_COLUMN_SCHEMA as $field => $config) {
            $candidates = array_merge([$field], $config['aliases'] ?? []);

            foreach ($candidates as $candidate) {
                $normalizedCandidate = $this->normalizeHeader($candidate);

                if ($normalizedCandidate === '' || ! array_key_exists($normalizedCandidate, $normalizedHeaders)) {
                    continue;
                }

                $resolved[$field] = $normalizedHeaders[$normalizedCandidate];
                break;
            }
        }

        $usedIndexes = array_values($resolved);

        foreach (array_keys(self::CSV_COLUMN_SCHEMA) as $field) {
            if (array_key_exists($field, $resolved)) {
                continue;
            }

            $index = $this->findBestDynamicHeaderMatch($field, $normalizedHeaders, $usedIndexes);

            if (! is_int($index)) {
                continue;
            }

            $resolved[$field] = $index;
            $usedIndexes[] = $index;
        }

        return $resolved;
    }

    /**
     * @param  array<int, string|null>  $headerRow
     * @return array<string, int>
     */
    private function resolveCsvHeaderIndex(array $headerRow): array
    {
        return $this->resolveCsvHeaders($headerRow)['header_index'];
    }

    /**
     * @param  array<string, int>  $normalizedHeaders
     * @param  array<int, int>  $usedIndexes
     */
    private function findBestDynamicHeaderMatch(string $field, array $normalizedHeaders, array $usedIndexes): ?int
    {
        $tokenRules = [
            'owner_full_name' => [['owner', 'full'], ['owner', 'name'], ['primary', 'owner']],
            'property_full_address' => [['property', 'full', 'address'], ['full', 'address'], ['mailing', 'address']],
            'property_address' => [['property', 'address'], ['address', 'line', '1'], ['street']],
            'property_city' => [['property', 'city'], ['city']],
            'property_state' => [['property', 'state'], ['state'], ['province']],
            'property_zip' => [['property', 'zip'], ['zip'], ['postal', 'code'], ['postcode']],
            'phone' => [['phone'], ['mobile'], ['cell']],
            'owner_2_full_name' => [['owner', '2', 'full'], ['owner', '2', 'name'], ['secondary', 'owner'], ['co', 'owner']],
            'owner_2_phone' => [['owner', '2', 'phone'], ['secondary', 'phone'], ['co', 'owner', 'phone']],
            'email' => [['email']],
            'owner_2_email' => [['owner', '2', 'email'], ['secondary', 'email'], ['co', 'owner', 'email']],
            'notes' => [['notes'], ['comments'], ['comment']],
        ];

        $rules = $tokenRules[$field] ?? [];

        if ($rules === []) {
            return null;
        }

        $bestIndex = null;
        $bestScore = 0;

        foreach ($normalizedHeaders as $normalizedHeader => $index) {
            if (in_array($index, $usedIndexes, true)) {
                continue;
            }

            $tokens = $this->headerTokens($normalizedHeader);

            foreach ($rules as $rule) {
                if (! $this->headerContainsAllTokens($tokens, $rule)) {
                    continue;
                }

                $score = count($rule);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestIndex = $index;
                }
            }
        }

        return $bestIndex;
    }

    /**
     * @return array<int, string>
     */
    private function headerTokens(string $normalizedHeader): array
    {
        $parts = preg_split('/\s+/', trim($normalizedHeader)) ?: [];

        return array_values(array_filter($parts, fn (string $part): bool => $part !== ''));
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<int, string>  $requiredTokens
     */
    private function headerContainsAllTokens(array $tokens, array $requiredTokens): bool
    {
        foreach ($requiredTokens as $requiredToken) {
            if (! in_array($requiredToken, $tokens, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $mapped
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $normalizedHeaders
     */
    private function enrichMappedRow(array &$mapped, array $row, array $normalizedHeaders): void
    {
        if (trim((string) ($mapped['owner_full_name'] ?? '')) === '') {
            $mapped['owner_full_name'] = $this->combineNameParts($row, $normalizedHeaders, [
                ['owner 1 first', 'owner first', 'first name'],
                ['owner 1 middle', 'owner middle', 'middle name'],
                ['owner 1 last', 'owner last', 'last name'],
            ]);
        }

        if (trim((string) ($mapped['owner_2_full_name'] ?? '')) === '') {
            $mapped['owner_2_full_name'] = $this->combineNameParts($row, $normalizedHeaders, [
                ['owner 2 first', 'co owner first', 'secondary owner first'],
                ['owner 2 middle', 'co owner middle', 'secondary owner middle'],
                ['owner 2 last', 'co owner last', 'secondary owner last'],
            ]);
        }

        if (trim((string) ($mapped['property_full_address'] ?? '')) === '') {
            $addressLine = trim((string) ($mapped['property_address'] ?? ''));
            $city = trim((string) ($mapped['property_city'] ?? ''));
            $state = trim((string) ($mapped['property_state'] ?? ''));
            $zip = trim((string) ($mapped['property_zip'] ?? ''));

            $mapped['property_full_address'] = $this->composeAddress($addressLine, $city, $state, $zip);
        }

        if (trim((string) ($mapped['property_zip'] ?? '')) === '') {
            $mapped['property_zip'] = $this->firstValueFromHeaders($row, $normalizedHeaders, [
                'property zip plus 4',
                'zip plus 4',
            ]);
        }
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $normalizedHeaders
     * @param  array<int, array<int, string>>  $partHeaderCandidates
     */
    private function combineNameParts(array $row, array $normalizedHeaders, array $partHeaderCandidates): string
    {
        $parts = [];

        foreach ($partHeaderCandidates as $candidates) {
            $value = $this->firstValueFromHeaders($row, $normalizedHeaders, $candidates);

            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return trim(implode(' ', $parts));
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $normalizedHeaders
     * @param  array<int, string>  $candidates
     */
    private function firstValueFromHeaders(array $row, array $normalizedHeaders, array $candidates): string
    {
        foreach ($candidates as $candidate) {
            $normalizedCandidate = $this->normalizeHeader($candidate);
            $index = $normalizedHeaders[$normalizedCandidate] ?? null;

            if (! is_int($index)) {
                continue;
            }

            $value = trim((string) ($row[$index] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function composeAddress(string $addressLine, string $city, string $state, string $zip): string
    {
        if ($addressLine === '') {
            return '';
        }

        $locality = trim(implode(', ', array_values(array_filter([$city, $state], fn (string $part): bool => $part !== ''))));

        if ($locality === '' && $zip === '') {
            return $addressLine;
        }

        if ($locality !== '' && $zip !== '') {
            return trim($addressLine.', '.$locality.' '.$zip);
        }

        if ($locality !== '') {
            return trim($addressLine.', '.$locality);
        }

        return trim($addressLine.' '.$zip);
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
        ProspectingSession::query()->updateOrCreate([
            'account_id' => $accountId,
            'user_id' => $userId,
        ], [
            'csv_filename' => trim($csvFilename) !== '' ? trim($csvFilename) : null,
            'state' => $state,
        ]);
    }

    /**
     * @return array{id: string, db_id: int, name: string, content: string, sort_order: int, is_private: bool}
     */
    private function scriptPayload(ProspectingScript $script): array
    {
        return [
            'id' => 'script-'.$script->id,
            'db_id' => (int) $script->id,
            'name' => $script->name,
            'content' => $script->content,
            'sort_order' => (int) $script->sort_order,
            'is_private' => $script->user_id !== null,
        ];
    }
}
