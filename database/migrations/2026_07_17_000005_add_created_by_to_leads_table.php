<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('created_by')
                ->nullable()
                ->after('assigned_to')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('leads')
            ->whereNull('created_by')
            ->whereNotNull('assigned_to')
            ->update(['created_by' => DB::raw('assigned_to')]);

        DB::table('leads')
            ->whereNull('created_by')
            ->whereNotNull('account_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $creatorId = DB::table('users')
                        ->where('account_id', $row->account_id)
                        ->whereIn('role', ['owner', 'admin', 'manager', 'agent'])
                        ->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 WHEN 'manager' THEN 3 WHEN 'agent' THEN 4 ELSE 5 END")
                        ->orderBy('id')
                        ->value('id');

                    if ($creatorId !== null) {
                        DB::table('leads')
                            ->where('id', $row->id)
                            ->update(['created_by' => $creatorId]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
