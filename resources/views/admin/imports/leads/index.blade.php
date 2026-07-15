<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold lp-title leading-tight">
            {{ __('Lead Import') }}
        </h2>
    </x-slot>

    <div class="lp-shell py-8">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            @include('admin.partials.sidebar')

            <section class="space-y-6">
                @if (session('status'))
                    <div class="rounded-xl border border-[#d3e2d0] bg-[#f5fbf4] p-4 text-sm text-[#2f5f34]">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('import_errors'))
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <p class="font-semibold">Import warnings (first 10):</p>
                        <ul class="mt-2 list-disc pl-5">
                            @foreach (session('import_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <article class="lp-card p-6 sm:p-8">
                    <p class="text-xs uppercase tracking-[0.2em] lp-muted">Step 1</p>
                    <h1 class="mt-2 text-2xl font-semibold lp-title">Download Template CSV</h1>
                    <p class="mt-2 text-sm lp-muted">
                        Start by downloading the official template. Keep the header row unchanged when adding your lead data.
                    </p>

                    <a href="{{ route('admin.imports.leads.template') }}" class="mt-5 inline-flex rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary">
                        Download template.csv
                    </a>
                </article>

                <article class="lp-card p-6 sm:p-8">
                    <p class="text-xs uppercase tracking-[0.2em] lp-muted">Step 2</p>
                    <h2 class="mt-2 text-2xl font-semibold lp-title">Upload Completed CSV</h2>
                    <p class="mt-2 text-sm lp-muted">
                        Upload your completed file to import leads. Required headers: first_name, last_name, email, phone, inquiry_type. Email, phone, and inquiry_type values can be left blank.
                    </p>

                    <form method="POST" action="{{ route('admin.imports.leads.upload') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                        @csrf

                        <div>
                            <label class="mb-1 block text-sm font-medium lp-title">CSV File</label>
                            <input name="csv_file" type="file" accept=".csv,text/csv" required class="w-full rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm" />
                            <p class="mt-2 text-xs lp-muted">Accepted inquiry_type values: buyer, seller, home_value (or home value), generic_inquiry (or generic inquiry). Leave blank if unknown.</p>
                            @error('csv_file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-medium lp-btn-primary">
                            Upload and Import
                        </button>
                    </form>
                </article>

                <article class="lp-card p-6 sm:p-8">
                    <p class="text-xs uppercase tracking-[0.2em] lp-muted">Optional</p>
                    <h2 class="mt-2 text-2xl font-semibold lp-title">Export Current Leads</h2>
                    <p class="mt-2 text-sm lp-muted">
                        Download existing lead data as CSV for backup, review, or spreadsheet analysis.
                    </p>

                    <a href="{{ route('admin.imports.leads.export') }}" class="mt-5 inline-flex rounded-xl border border-[var(--lp-border)] px-4 py-2.5 text-sm font-medium lp-title hover:bg-[var(--lp-canvas)]">
                        Export leads.csv
                    </a>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
