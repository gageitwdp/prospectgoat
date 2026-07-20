<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Prospecting Tool') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8" x-data="prospectingTool()">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="space-y-6">
                <article class="lp-card p-6 sm:p-8">
                    <p class="text-xs uppercase tracking-[0.2em] lp-muted">Prospect Lead Import</p>
                    <h1 class="mt-2 text-2xl font-semibold lp-title">Load Prospect CSV</h1>
                    <p class="mt-2 text-sm lp-muted">
                        This workflow is dedicated to prospecting leads and does not replace the existing lead import module.
                    </p>

                    <p class="mt-2 text-xs lp-muted" x-show="loadedFileName" x-cloak>
                        Last loaded file: <span class="font-medium lp-title" x-text="loadedFileName"></span>
                    </p>

                    <form class="mt-6 space-y-4" @submit.prevent="parseCsv">
                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">CSV File</label>
                            <input x-ref="csvFile" name="csv_file" type="file" accept=".csv,text/csv" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                            <p class="mt-2 text-xs lp-muted">
                                Minimum required columns: owner name and full property address. Common header variants are supported (for example Owner Name, Owner 1 Full Name, Property Address, Full Address, ZIP Code).
                            </p>
                        </div>

                        <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary" :disabled="loadingParse">
                            <span x-show="!loadingParse">Load CSV</span>
                            <span x-show="loadingParse" x-cloak>Loading...</span>
                        </button>
                    </form>

                    <p class="mt-4 text-sm text-emerald-700" x-text="parseSuccess" x-show="parseSuccess" x-cloak></p>
                    <p class="mt-2 text-sm text-red-600" x-text="parseError" x-show="parseError" x-cloak></p>
                </article>

                <article class="lp-card p-6 sm:p-8" x-show="rows.length > 0" x-cloak>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] lp-muted">Current Prospect</p>
                            <h2 class="mt-1 text-2xl font-semibold lp-title" x-text="currentRow?.owner_full_name || 'Prospect'"></h2>
                        </div>
                        <span class="rounded-full bg-[var(--lp-canvas)] px-3 py-1 text-xs lp-muted" x-text="`Card ${currentIndex + 1} of ${rows.length}`"></span>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-[var(--lp-border)] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-xs uppercase tracking-[0.12em] lp-muted">Owner Full Name</p>
                                <button
                                    type="button"
                                    class="rounded-md border border-[var(--lp-border)] p-1.5 lp-muted transition hover:bg-[var(--lp-canvas)] hover:lp-title"
                                    @click="copyField(currentRow?.owner_full_name || '', 'Owner full name')"
                                    :disabled="!(currentRow?.owner_full_name || '').trim()"
                                    aria-label="Copy owner full name"
                                    title="Copy"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125H5.25a1.125 1.125 0 0 1-1.125-1.125V8.25c0-.621.504-1.125 1.125-1.125h3.375" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 15.75h9A1.5 1.5 0 0 0 20.25 14.25v-9a1.5 1.5 0 0 0-1.5-1.5h-9a1.5 1.5 0 0 0-1.5 1.5v9a1.5 1.5 0 0 0 1.5 1.5Z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-sm lp-title" x-text="currentRow?.owner_full_name || 'N/A'"></p>
                        </div>
                        <div class="rounded-xl border border-[var(--lp-border)] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-xs uppercase tracking-[0.12em] lp-muted">Property Full Address</p>
                                <button
                                    type="button"
                                    class="rounded-md border border-[var(--lp-border)] p-1.5 lp-muted transition hover:bg-[var(--lp-canvas)] hover:lp-title"
                                    @click="copyField(currentRow?.property_full_address || '', 'Property full address')"
                                    :disabled="!(currentRow?.property_full_address || '').trim()"
                                    aria-label="Copy property full address"
                                    title="Copy"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125H5.25a1.125 1.125 0 0 1-1.125-1.125V8.25c0-.621.504-1.125 1.125-1.125h3.375" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 15.75h9A1.5 1.5 0 0 0 20.25 14.25v-9a1.5 1.5 0 0 0-1.5-1.5h-9a1.5 1.5 0 0 0-1.5 1.5v9a1.5 1.5 0 0 0 1.5 1.5Z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-sm lp-title" x-text="currentRow?.property_full_address || 'N/A'"></p>
                        </div>
                        <div class="rounded-xl border border-[var(--lp-border)] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-xs uppercase tracking-[0.12em] lp-muted">Phone Number</p>
                                <button
                                    type="button"
                                    class="rounded-md border border-[var(--lp-border)] p-1.5 lp-muted transition hover:bg-[var(--lp-canvas)] hover:lp-title"
                                    @click="copyField(currentEdit().phone || '', 'Phone number')"
                                    :disabled="!(currentEdit().phone || '').trim()"
                                    aria-label="Copy phone number"
                                    title="Copy"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125H5.25a1.125 1.125 0 0 1-1.125-1.125V8.25c0-.621.504-1.125 1.125-1.125h3.375" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 15.75h9A1.5 1.5 0 0 0 20.25 14.25v-9a1.5 1.5 0 0 0-1.5-1.5h-9a1.5 1.5 0 0 0-1.5 1.5v9a1.5 1.5 0 0 0 1.5 1.5Z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-sm lp-title" x-text="effectivePhone()"></p>
                        </div>
                        <div class="rounded-xl border border-[var(--lp-border)] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-xs uppercase tracking-[0.12em] lp-muted">Email</p>
                                <button
                                    type="button"
                                    class="rounded-md border border-[var(--lp-border)] p-1.5 lp-muted transition hover:bg-[var(--lp-canvas)] hover:lp-title"
                                    @click="copyField(currentEdit().email || '', 'Email')"
                                    :disabled="!(currentEdit().email || '').trim()"
                                    aria-label="Copy email"
                                    title="Copy"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125H5.25a1.125 1.125 0 0 1-1.125-1.125V8.25c0-.621.504-1.125 1.125-1.125h3.375" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 15.75h9A1.5 1.5 0 0 0 20.25 14.25v-9a1.5 1.5 0 0 0-1.5-1.5h-9a1.5 1.5 0 0 0-1.5 1.5v9a1.5 1.5 0 0 0 1.5 1.5Z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-sm lp-title" x-text="effectiveEmail()"></p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs uppercase tracking-[0.12em] lp-muted">Owner 2 Full Name</label>
                            <input
                                type="text"
                                class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm"
                                x-model="currentEdit().owner_2_full_name"
                                @input="scheduleSessionPersist"
                                placeholder="Owner 2 full name"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs uppercase tracking-[0.12em] lp-muted">Owner 2 Phone</label>
                            <input
                                type="text"
                                class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm"
                                x-model="currentEdit().owner_2_phone"
                                @input="scheduleSessionPersist"
                                placeholder="555-123-4567"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs uppercase tracking-[0.12em] lp-muted">Owner 2 Email</label>
                            <input
                                type="email"
                                class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm"
                                x-model="currentEdit().owner_2_email"
                                @input="scheduleSessionPersist"
                                placeholder="owner2@example.com"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2">
                        <button type="button" class="rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm font-medium lp-title hover:bg-[var(--lp-canvas)]" @click="previousCard" :disabled="currentIndex === 0">
                            Previous
                        </button>
                        <button type="button" class="rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm font-medium lp-title hover:bg-[var(--lp-canvas)]" @click="nextCard" :disabled="currentIndex >= rows.length - 1">
                            Next
                        </button>
                        <button type="button" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary" @click="openBeenVerified">
                            BeenVerified Lookup
                        </button>
                        <button type="button" class="rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm font-medium lp-title hover:bg-[var(--lp-canvas)]" @click="openPhoneModal">
                            Add Phone
                        </button>
                        <button type="button" class="rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm font-medium lp-title hover:bg-[var(--lp-canvas)]" @click="openEmailModal">
                            Add Email
                        </button>
                        <button type="button" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-accent" @click="saveLead" :disabled="savingLead">
                            <span x-show="!savingLead">Save Lead</span>
                            <span x-show="savingLead" x-cloak>Saving...</span>
                        </button>
                    </div>

                    <p class="mt-4 text-sm text-emerald-700" x-text="saveSuccess" x-show="saveSuccess" x-cloak></p>
                    <p class="mt-2 text-sm text-emerald-700" x-text="copySuccess" x-show="copySuccess" x-cloak></p>
                    <p class="mt-2 text-sm text-red-600" x-text="saveError" x-show="saveError" x-cloak></p>
                    <p class="mt-2 text-sm text-red-600" x-text="copyError" x-show="copyError" x-cloak></p>
                    <p class="mt-2 text-xs lp-muted" x-show="savedRows[currentIndex]" x-cloak>This card has already been saved in this session.</p>
                </article>

                <article class="lp-card p-6 sm:p-8" x-show="rows.length > 0" x-cloak>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] lp-muted">Scripts</p>
                            <h2 class="mt-1 text-2xl font-semibold lp-title">Prospecting Script Library</h2>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm font-medium lp-title hover:bg-[var(--lp-canvas)]"
                                @click="openCreateScriptModal()"
                            >
                                New Private Script
                            </button>
                            <button
                                type="button"
                                class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary"
                                @click="openScriptContentModal()"
                                :disabled="!activeScript"
                            >
                                Edit Script Content
                            </button>
                        </div>
                    </div>

                    <p class="mt-3 text-xs lp-muted">
                        Private scripts are only visible to your user.
                    </p>
                    <p class="mt-2 text-sm text-emerald-700" x-show="scriptSaveSuccess" x-text="scriptSaveSuccess" x-cloak></p>
                    <p class="mt-2 text-sm text-red-600" x-show="scriptSaveError" x-text="scriptSaveError" x-cloak></p>

                    <div class="mt-5">
                        <label class="mb-1 block text-xs uppercase tracking-[0.12em] lp-muted">Your Phone Number</label>
                        <input
                            type="text"
                            class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm sm:max-w-sm"
                            x-model="scriptPhone"
                            @input="scheduleSessionPersist"
                            placeholder="Your phone number"
                        />
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2 border-b border-[var(--lp-border)] pb-3">
                        <template x-for="(script, index) in scripts" :key="script.id">
                            <button
                                type="button"
                                class="rounded-t-lg border px-4 py-2 text-sm font-medium transition"
                                :class="activeScriptIndex === index ? 'border-[var(--lp-secondary)] bg-[var(--lp-secondary)] text-white' : 'border-[var(--lp-border)] lp-title hover:bg-[var(--lp-canvas)]'"
                                @click="activeScriptIndex = index"
                            >
                                <span x-text="script.name"></span>
                                <span x-show="script.is_private" x-cloak class="ml-2 rounded-full border border-[var(--lp-border)] px-2 py-0.5 text-[10px] uppercase tracking-[0.08em]">Private</span>
                            </button>
                        </template>
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-2" x-show="activeScript" x-cloak>
                        <div class="rounded-xl border border-[var(--lp-border)] bg-[var(--lp-canvas)] p-4">
                            <p class="text-xs uppercase tracking-[0.12em] lp-muted">Preview</p>
                            <p class="mt-2 text-sm font-medium lp-title" x-text="activeScript?.name || 'Script'"></p>
                            <pre class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 lp-muted" x-text="activeScript?.content || ''"></pre>
                        </div>

                        <div class="rounded-xl border border-[var(--lp-border)] bg-[var(--lp-canvas)] p-4">
                            <label class="text-xs uppercase tracking-[0.12em] lp-muted">Notes</label>
                            <textarea
                                rows="9"
                                class="mt-3 w-full rounded-xl border border-[var(--lp-border)] px-4 py-3 text-sm leading-6"
                                x-model="currentEdit().notes"
                                @input="scheduleSessionPersist"
                                placeholder="Add notes for this prospect..."
                            ></textarea>
                        </div>
                    </div>
                </article>

                <article class="lp-card p-6 sm:p-8" x-show="rows.length === 0" x-cloak>
                    <p class="text-sm lp-muted">
                        Upload your prospect CSV to begin reviewing one card at a time.
                    </p>
                </article>
            </section>
        </div>
        <x-modal name="prospecting-phone-modal" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-semibold lp-title">Add Phone Number</h3>
                <p class="mt-2 text-sm lp-muted">Enter the phone number for this prospect card.</p>
                <input type="text" class="mt-4 w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" x-model="modalPhone" placeholder="555-123-4567" />
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-xl border border-[var(--lp-border)] px-4 py-2 text-sm lp-title hover:bg-[var(--lp-canvas)]" @click="$dispatch('close-modal', 'prospecting-phone-modal')">Cancel</button>
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium lp-btn-primary" @click="applyPhone">Save Phone</button>
                </div>
            </div>
        </x-modal>

        <x-modal name="prospecting-email-modal" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-semibold lp-title">Add Email</h3>
                <p class="mt-2 text-sm lp-muted">Enter the email address for this prospect card.</p>
                <input type="email" class="mt-4 w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" x-model="modalEmail" placeholder="name@example.com" />
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-xl border border-[var(--lp-border)] px-4 py-2 text-sm lp-title hover:bg-[var(--lp-canvas)]" @click="$dispatch('close-modal', 'prospecting-email-modal')">Cancel</button>
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium lp-btn-primary" @click="applyEmail">Save Email</button>
                </div>
            </div>
        </x-modal>

        <x-modal name="prospecting-script-content-modal" maxWidth="2xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold lp-title">Edit Script Content</h3>
                <p class="mt-2 text-sm lp-muted">Update the content for the active script.</p>
                <textarea
                    rows="14"
                    class="mt-4 w-full rounded-xl border border-[var(--lp-border)] px-4 py-3 text-sm leading-6"
                    x-model="scriptContentDraft"
                ></textarea>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-xl border border-[var(--lp-border)] px-4 py-2 text-sm lp-title hover:bg-[var(--lp-canvas)]" @click="$dispatch('close-modal', 'prospecting-script-content-modal')">Cancel</button>
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium lp-btn-primary" :disabled="savingScript" @click="applyScriptContent">
                        <span x-show="!savingScript">Save Changes</span>
                        <span x-show="savingScript" x-cloak>Saving...</span>
                    </button>
                </div>
            </div>
        </x-modal>

        <x-modal name="prospecting-script-create-modal" maxWidth="2xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold lp-title">Create Private Script</h3>
                <p class="mt-2 text-sm lp-muted">Only your user can see and edit this script.</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title" for="private_script_name">Script Name</label>
                        <input id="private_script_name" type="text" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" x-model="newScriptForm.name" maxlength="120" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium lp-title" for="private_script_content">Script Content</label>
                        <textarea id="private_script_content" rows="12" class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-3 text-sm leading-6" x-model="newScriptForm.content"></textarea>
                    </div>
                </div>

                <p class="mt-3 text-sm text-red-600" x-show="scriptSaveError" x-text="scriptSaveError" x-cloak></p>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-xl border border-[var(--lp-border)] px-4 py-2 text-sm lp-title hover:bg-[var(--lp-canvas)]" @click="$dispatch('close-modal', 'prospecting-script-create-modal')">Cancel</button>
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium lp-btn-primary" :disabled="savingScript" @click="createPrivateScript">
                        <span x-show="!savingScript">Create Script</span>
                        <span x-show="savingScript" x-cloak>Creating...</span>
                    </button>
                </div>
            </div>
        </x-modal>
    </div>

    <script>
        function prospectingTool() {
            return {
                rows: [],
                currentIndex: 0,
                edits: {},
                scripts: @js($scripts),
                activeScriptIndex: 0,
                scriptContentDraft: '',
                loadedFileName: '',
                savedRows: {},
                loadingParse: false,
                savingLead: false,
                savingSessionState: false,
                pendingSessionPersist: false,
                persistTimer: null,
                hideHandler: null,
                parseSuccess: '',
                parseError: '',
                saveSuccess: '',
                saveError: '',
                copySuccess: '',
                copyError: '',
                scriptSaveSuccess: '',
                scriptSaveError: '',
                modalPhone: '',
                modalEmail: '',
                scriptPhone: '',
                newScriptForm: {
                    name: '',
                    content: '',
                },
                savingScript: false,
                restoredSession: @js($prospectingSession),
                parseUrl: @js(route('admin.prospecting.parse-csv')),
                privateScriptStoreUrl: @js(route('admin.prospecting.scripts.store')),
                privateScriptUpdateUrlTemplate: @js(route('admin.prospecting.scripts.update', ['prospectingScript' => '__SCRIPT_ID__'])),
                sessionStateUrl: @js(route('admin.prospecting.session-state')),
                saveUrl: @js(route('admin.prospecting.save-lead')),
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

                init() {
                    this.restoreSessionState();

                    this.hideHandler = () => {
                        this.persistSessionState({ useKeepalive: true });
                    };

                    window.addEventListener('pagehide', this.hideHandler);
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'hidden') {
                            this.hideHandler();
                        }
                    });
                },

                get currentRow() {
                    return this.rows[this.currentIndex] || null;
                },

                get activeScript() {
                    return this.scripts[this.activeScriptIndex] || null;
                },

                currentEdit() {
                    if (!this.edits[this.currentIndex]) {
                        this.edits[this.currentIndex] = {
                            phone: '',
                            email: '',
                            owner_2_full_name: '',
                            owner_2_phone: '',
                            owner_2_email: '',
                            notes: '',
                        };
                    }

                    return this.edits[this.currentIndex];
                },

                effectivePhone() {
                    const phone = this.currentEdit().phone.trim();
                    if (phone === '') {
                        return 'Not provided';
                    }

                    const digitsOnly = phone.replace(/\D/g, '');

                    if (digitsOnly.length === 10) {
                        return `(${digitsOnly.slice(0, 3)}) ${digitsOnly.slice(3, 6)}-${digitsOnly.slice(6)}`;
                    }

                    if (digitsOnly.length === 11 && digitsOnly.startsWith('1')) {
                        return `+1 (${digitsOnly.slice(1, 4)}) ${digitsOnly.slice(4, 7)}-${digitsOnly.slice(7)}`;
                    }

                    return phone;
                },

                effectiveEmail() {
                    const email = this.currentEdit().email.trim();
                    return email !== '' ? email : 'Not provided';
                },

                clearCopyMessages() {
                    this.copySuccess = '';
                    this.copyError = '';
                },

                restoreSessionState() {
                    const session = this.restoredSession;

                    if (!session || typeof session !== 'object' || !session.state || typeof session.state !== 'object') {
                        return;
                    }

                    const state = session.state;

                    this.rows = Array.isArray(state.rows) ? state.rows : [];
                    this.currentIndex = Number.isInteger(state.current_index) ? state.current_index : 0;
                    this.edits = state.edits && typeof state.edits === 'object' ? state.edits : {};
                    this.savedRows = state.saved_rows && typeof state.saved_rows === 'object' ? state.saved_rows : {};
                    this.scriptPhone = typeof state.script_phone === 'string' ? state.script_phone : '';
                    this.loadedFileName = typeof session.csv_filename === 'string' ? session.csv_filename : '';

                    if (this.rows.length === 0) {
                        this.currentIndex = 0;
                        return;
                    }

                    if (this.currentIndex < 0) {
                        this.currentIndex = 0;
                    }

                    if (this.currentIndex >= this.rows.length) {
                        this.currentIndex = this.rows.length - 1;
                    }
                },

                scheduleSessionPersist() {
                    if (this.persistTimer) {
                        clearTimeout(this.persistTimer);
                    }

                    this.persistTimer = setTimeout(() => {
                        this.persistSessionState();
                    }, 250);
                },

                async persistSessionState(options = {}) {
                    const useKeepalive = options.useKeepalive === true;

                    if (this.savingSessionState && !useKeepalive) {
                        this.pendingSessionPersist = true;
                        return;
                    }

                    if (!useKeepalive) {
                        this.savingSessionState = true;
                        this.pendingSessionPersist = false;
                    }

                    try {
                        await fetch(this.sessionStateUrl, {
                            method: 'POST',
                            keepalive: useKeepalive,
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                csv_filename: this.loadedFileName || null,
                                current_index: this.currentIndex,
                                edits: this.edits,
                                saved_rows: this.savedRows,
                                script_phone: this.scriptPhone,
                            }),
                        });
                    } catch (error) {
                        // Intentionally silent: state save should not block prospecting workflow.
                    } finally {
                        if (useKeepalive) {
                            return;
                        }

                        this.savingSessionState = false;

                        if (this.pendingSessionPersist) {
                            this.pendingSessionPersist = false;
                            this.persistSessionState();
                        }
                    }
                },

                openScriptContentModal() {
                    this.scriptSaveError = '';
                    this.scriptSaveSuccess = '';
                    this.scriptContentDraft = this.activeScript?.content || '';
                    this.$dispatch('open-modal', 'prospecting-script-content-modal');
                },

                async applyScriptContent() {
                    if (this.activeScriptIndex >= 0 && this.activeScriptIndex < this.scripts.length) {
                        this.scripts[this.activeScriptIndex].content = this.scriptContentDraft;

                        const script = this.scripts[this.activeScriptIndex];

                        if (script?.is_private && Number.isInteger(script.db_id)) {
                            this.savingScript = true;
                            this.scriptSaveError = '';

                            try {
                                const response = await fetch(this.privateScriptUpdateUrl(Number(script.db_id)), {
                                    method: 'PUT',
                                    headers: {
                                        'X-CSRF-TOKEN': this.csrfToken,
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        name: script.name || 'Private Script',
                                        content: this.scriptContentDraft,
                                        sort_order: Number(script.sort_order || 0),
                                        is_active: true,
                                    }),
                                });

                                const data = await response.json();

                                if (!response.ok) {
                                    this.scriptSaveError = data.message || 'Unable to save private script.';
                                    return;
                                }

                                if (data.script && typeof data.script === 'object') {
                                    this.scripts[this.activeScriptIndex] = data.script;
                                }
                            } catch (error) {
                                this.scriptSaveError = 'Unable to save private script.';
                                return;
                            } finally {
                                this.savingScript = false;
                            }
                        }
                    }

                    this.scheduleSessionPersist();
                    this.$dispatch('close-modal', 'prospecting-script-content-modal');
                },

                openCreateScriptModal() {
                    this.scriptSaveError = '';
                    this.scriptSaveSuccess = '';
                    this.newScriptForm = {
                        name: '',
                        content: '',
                    };
                    this.$dispatch('open-modal', 'prospecting-script-create-modal');
                },

                privateScriptUpdateUrl(scriptId) {
                    return this.privateScriptUpdateUrlTemplate.replace('__SCRIPT_ID__', String(scriptId));
                },

                async createPrivateScript() {
                    const name = (this.newScriptForm.name || '').trim();
                    const content = (this.newScriptForm.content || '').trim();

                    this.scriptSaveError = '';
                    this.scriptSaveSuccess = '';

                    if (name === '' || content === '') {
                        this.scriptSaveError = 'Script name and content are required.';
                        return;
                    }

                    this.savingScript = true;

                    try {
                        const response = await fetch(this.privateScriptStoreUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                name,
                                content,
                                is_active: true,
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            this.scriptSaveError = data.message || 'Unable to create private script.';
                            return;
                        }

                        if (!data.script || typeof data.script !== 'object') {
                            this.scriptSaveError = 'Unable to create private script.';
                            return;
                        }

                        this.scripts.push(data.script);
                        this.activeScriptIndex = this.scripts.length - 1;
                        this.scriptSaveSuccess = data.message || 'Private script created.';
                        this.scheduleSessionPersist();
                        this.$dispatch('close-modal', 'prospecting-script-create-modal');
                    } catch (error) {
                        this.scriptSaveError = 'Unable to create private script.';
                    } finally {
                        this.savingScript = false;
                    }
                },

                async copyField(value, label) {
                    const text = String(value || '').trim();
                    this.clearCopyMessages();

                    if (text === '') {
                        this.copyError = `No ${label.toLowerCase()} to copy.`;
                        return;
                    }

                    try {
                        if (navigator.clipboard?.writeText) {
                            await navigator.clipboard.writeText(text);
                        } else {
                            const tempInput = document.createElement('textarea');
                            tempInput.value = text;
                            tempInput.setAttribute('readonly', '');
                            tempInput.style.position = 'absolute';
                            tempInput.style.left = '-9999px';
                            document.body.appendChild(tempInput);
                            tempInput.select();
                            document.execCommand('copy');
                            document.body.removeChild(tempInput);
                        }

                        this.copySuccess = `${label} copied to clipboard.`;
                        setTimeout(() => {
                            if (this.copySuccess === `${label} copied to clipboard.`) {
                                this.copySuccess = '';
                            }
                        }, 2000);
                    } catch (error) {
                        this.copyError = `Could not copy ${label.toLowerCase()}.`;
                    }
                },

                async parseCsv() {
                    this.parseError = '';
                    this.parseSuccess = '';
                    this.saveError = '';
                    this.saveSuccess = '';

                    const file = this.$refs.csvFile?.files?.[0];
                    if (!file) {
                        this.parseError = 'Please choose a CSV file first.';
                        return;
                    }

                    const formData = new FormData();
                    formData.append('csv_file', file);

                    this.loadingParse = true;

                    try {
                        const response = await fetch(this.parseUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            this.parseError = data.message || 'Unable to parse CSV file.';
                            return;
                        }

                        this.rows = Array.isArray(data.rows) ? data.rows : [];
                        this.currentIndex = 0;
                        this.edits = {};
                        this.savedRows = {};
                        this.loadedFileName = file.name;
                        this.clearCopyMessages();
                        this.parseSuccess = data.message || 'CSV loaded successfully.';
                        this.scheduleSessionPersist();
                    } catch (error) {
                        this.parseError = 'Unable to parse CSV file.';
                    } finally {
                        this.loadingParse = false;
                    }
                },

                previousCard() {
                    if (this.currentIndex > 0) {
                        this.currentIndex -= 1;
                        this.saveSuccess = '';
                        this.saveError = '';
                        this.clearCopyMessages();
                        this.persistSessionState();
                    }
                },

                nextCard() {
                    if (this.currentIndex < this.rows.length - 1) {
                        this.currentIndex += 1;
                        this.saveSuccess = '';
                        this.saveError = '';
                        this.clearCopyMessages();
                        this.persistSessionState();
                    }
                },

                openPhoneModal() {
                    this.modalPhone = this.currentEdit().phone;
                    this.$dispatch('open-modal', 'prospecting-phone-modal');
                },

                applyPhone() {
                    this.currentEdit().phone = this.modalPhone.trim();
                    this.scheduleSessionPersist();
                    this.$dispatch('close-modal', 'prospecting-phone-modal');
                },

                openEmailModal() {
                    this.modalEmail = this.currentEdit().email;
                    this.$dispatch('open-modal', 'prospecting-email-modal');
                },

                applyEmail() {
                    this.currentEdit().email = this.modalEmail.trim();
                    this.scheduleSessionPersist();
                    this.$dispatch('close-modal', 'prospecting-email-modal');
                },

                openBeenVerified() {
                    if (!this.currentRow) {
                        return;
                    }

                    const params = new URLSearchParams({
                        address: this.currentRow.property_address || '',
                        city: this.currentRow.property_city || '',
                        state: this.currentRow.property_state || '',
                        zipcode: this.currentRow.property_zip || '',
                        report_flags: JSON.stringify(['include_ca_data']),
                    });

                    const url = `https://www.beenverified.com/rf/report/property?${params.toString()}`;
                    window.open(url, '_blank', 'noopener');
                },

                async saveLead() {
                    if (!this.currentRow) {
                        this.saveError = 'No card is currently selected.';
                        return;
                    }

                    this.saveError = '';
                    this.saveSuccess = '';
                    this.savingLead = true;

                    const edit = this.currentEdit();

                    const payload = {
                        owner_full_name: this.currentRow.owner_full_name || '',
                        owner_2_full_name: edit.owner_2_full_name || '',
                        property_full_address: this.currentRow.property_full_address || '',
                        property_address: this.currentRow.property_address || '',
                        property_city: this.currentRow.property_city || '',
                        property_state: this.currentRow.property_state || '',
                        property_zip: this.currentRow.property_zip || '',
                        phone: edit.phone || '',
                        owner_2_phone: edit.owner_2_phone || '',
                        email: edit.email || '',
                        owner_2_email: edit.owner_2_email || '',
                        notes: edit.notes || '',
                    };

                    try {
                        const response = await fetch(this.saveUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await response.json();

                        if (response.status === 409) {
                            this.saveError = data.message || 'This lead already exists and was skipped.';
                            return;
                        }

                        if (!response.ok) {
                            this.saveError = data.message || 'Unable to save lead.';
                            return;
                        }

                        this.savedRows[this.currentIndex] = true;
                        this.saveSuccess = data.message || 'Lead saved successfully.';
                        this.scheduleSessionPersist();
                    } catch (error) {
                        this.saveError = 'Unable to save lead.';
                    } finally {
                        this.savingLead = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
