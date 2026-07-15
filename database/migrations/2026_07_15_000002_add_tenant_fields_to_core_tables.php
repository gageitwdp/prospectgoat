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
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('account_id')->nullable()->after('id');
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','owner','manager','agent') NOT NULL DEFAULT 'agent'");
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->index(['account_id', 'role']);
        });

        $this->addAccountColumn('leads');
        $this->addAccountColumn('lead_activities');
        $this->addAccountColumn('tasks');
        $this->addAccountColumn('events');
        $this->addAccountColumn('event_registrations');
        $this->addAccountColumn('email_templates');

        $defaultAccountId = $this->resolveDefaultAccountId();
        if ($defaultAccountId !== null) {
            DB::table('users')->whereNull('account_id')->update(['account_id' => $defaultAccountId]);
            $this->backfillAccountId('leads', $defaultAccountId);
            $this->backfillAccountId('lead_activities', $defaultAccountId);
            $this->backfillAccountId('tasks', $defaultAccountId);
            $this->backfillAccountId('events', $defaultAccountId);
            $this->backfillAccountId('event_registrations', $defaultAccountId);
            $this->backfillAccountId('email_templates', $defaultAccountId);
        }

        DB::table('users')->where('role', 'admin')->update(['role' => 'owner']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropAccountColumn('email_templates');
        $this->dropAccountColumn('event_registrations');
        $this->dropAccountColumn('events');
        $this->dropAccountColumn('tasks');
        $this->dropAccountColumn('lead_activities');
        $this->dropAccountColumn('leads');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['account_id', 'role']);
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','agent') NOT NULL DEFAULT 'agent'");
        }

        DB::table('users')->where('role', 'owner')->update(['role' => 'admin']);
    }

    private function resolveDefaultAccountId(): ?int
    {
        $existing = DB::table('accounts')->orderBy('id')->value('id');
        if ($existing !== null) {
            return (int) $existing;
        }

        return null;
    }

    private function backfillAccountId(string $tableName, int $accountId): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'account_id')) {
            return;
        }

        DB::table($tableName)->whereNull('account_id')->update(['account_id' => $accountId]);
    }

    private function addAccountColumn(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (! Schema::hasColumn($tableName, 'account_id')) {
                $table->unsignedBigInteger('account_id')->nullable()->after('id');
            }
        });

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->index('account_id');
        });
    }

    private function dropAccountColumn(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'account_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropIndex(['account_id']);
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
