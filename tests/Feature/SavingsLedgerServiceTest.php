<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Services\SavingsLedgerService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

/**
 * Builds its own minimal members/savings_ledger schema instead of running
 * the full migration set: two existing migrations (source_type enum
 * widening for 'rebate_release' / 'benefit_release') use raw MySQL
 * `ALTER TABLE ... MODIFY` syntax that SQLite (the configured test DB,
 * see phpunit.xml) rejects, so `RefreshDatabase` can't run here yet. That
 * cross-DB migration gap is pre-existing and separate from the
 * SavingsLedgerService bug this test targets.
 */
class SavingsLedgerServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_no')->unique();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('middlename')->nullable();
            $table->string('plate_number')->unique();
            $table->string('operator_name');
            $table->string('route');
            $table->string('contact_number')->nullable();
            $table->string('address')->nullable();
            $table->date('date_joined')->nullable();
            $table->string('status')->default('active');
            $table->decimal('savings_balance', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('savings_ledger', function (Blueprint $table) {
            $table->id('ledger_id');
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->date('date');
            $table->string('source_type');
            $table->string('txn_type');
            $table->decimal('amount', 12, 2);
            $table->decimal('running_balance', 12, 2);
            $table->nullableMorphs('sourceable');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('savings_ledger');
        Schema::dropIfExists('members');

        parent::tearDown();
    }

    public function test_record_writes_ledger_entry_with_correct_member_id(): void
    {
        $member = Member::factory()->create(['savings_balance' => 0]);

        $entry = app(SavingsLedgerService::class)->record(
            member: $member,
            date: '2026-08-26',
            sourceType: 'daily_dues',
            txnType: 'deposit',
            amount: 35.00,
        );

        $this->assertSame($member->id, $entry->member_id);
        $this->assertEquals(35.00, $entry->running_balance);
    }

    public function test_record_keeps_member_savings_balance_in_sync_with_ledger(): void
    {
        $member = Member::factory()->create(['savings_balance' => 0]);
        $service = app(SavingsLedgerService::class);

        $service->record($member, '2026-08-01', 'daily_dues', 'deposit', 35.00);
        $service->record($member, '2026-08-02', 'fuel_rebate', 'deposit', 2.00);
        $service->record($member, '2026-08-03', 'loan_deduction', 'withdrawal', 10.00);

        $member->refresh();

        $this->assertEquals(27.00, $member->savings_balance);
        $this->assertEquals(27.00, $member->savingsLedgers()->latest('ledger_id')->value('running_balance'));
    }

    public function test_record_accepts_benefit_release_source_type(): void
    {
        $member = Member::factory()->create(['savings_balance' => 0]);

        $entry = app(SavingsLedgerService::class)->record(
            member: $member,
            date: '2026-08-26',
            sourceType: 'benefit_release',
            txnType: 'deposit',
            amount: 700.00,
        );

        $this->assertSame('benefit_release', $entry->source_type);
        $this->assertEquals(700.00, $member->fresh()->savings_balance);
    }
}
