<?php

namespace Tests\Feature\Import;

use App\Models\DailyDue;
use App\Models\IncomeExpense;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Services\Import\DailyCollectionImporter;
use App\Services\Import\ExpensesImporter;
use App\Services\Import\MembersImporter;
use App\Services\Import\RentalImporter;
use App\Services\LoanScheduleService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->staff = $this->admin();
    }

    private function xlsx(array $headerAndRows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        foreach ($headerAndRows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }

    public function test_members_import_creates_members_with_defaults(): void
    {
        $path = $this->xlsx([
            ['member no', 'first name', 'last name', 'middle name', 'plate number', 'operator name', 'route', 'category', 'contact number', 'address', 'date joined', 'status'],
            ['MBR-5001', 'Rodolfo', 'Santos', 'Reyes', 'abc-1234', '', 'carmen', 'member', '09171234567', 'Carmen, CDO', '2021-03-15', 'active'],
            ['', 'Nenita', 'Fernandez', '', 'DEF-6789', '', 'cogon', 'non-member', '', '', '', ''],
            ['', 'NoPlate', 'Person', '', '', '', 'carmen', '', '', '', '', ''],
        ]);

        $log = app(MembersImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'members.xlsx');

        $this->assertSame(2, $log->imported);
        $this->assertSame(1, $log->skipped);

        $this->assertDatabaseHas('members', [
            'member_no' => 'MBR-5001',
            'plate_number' => 'ABC-1234',
            'operator_name' => 'Rodolfo Santos', // defaulted from the name
            'route' => 'Carmen',
            'category' => 'member',
        ]);

        $nenita = Member::where('plate_number', 'DEF-6789')->firstOrFail();
        $this->assertStringStartsWith('MBR-', $nenita->member_no); // auto number
        $this->assertSame('non-member', $nenita->category);
        $this->assertSame('active', $nenita->status); // default
        $this->assertSame(now()->toDateString(), $nenita->date_joined->toDateString()); // default
        $this->assertStringContainsString('Missing plate number', $log->errors[0]['message']);
    }

    public function test_members_import_skips_duplicate_plate(): void
    {
        Member::factory()->create(['plate_number' => 'ABC-9999']);

        $path = $this->xlsx([
            ['first name', 'last name', 'plate number', 'route'],
            ['New', 'Guy', 'ABC-9999', 'carmen'],
            ['Another', 'Guy', 'XYZ-0001', 'cogon'],
            ['Third', 'Guy', 'xyz-0001', 'carmen'], // dup within file (case-insensitive)
        ]);

        $log = app(MembersImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'm.xlsx');

        $this->assertSame(1, $log->imported);
        $this->assertSame(2, $log->skipped);
        $this->assertDatabaseCount('members', 2); // the factory one + XYZ-0001
    }

    public function test_daily_collection_import_creates_dues_tickets_and_fuel(): void
    {
        $member = Member::factory()->create(['plate_number' => 'ABC-1234', 'route' => 'Carmen']);
        $unknown = 'ZZZ-0000';

        $path = $this->xlsx([
            ['name', 'route', 'plate number', 'total amount', 'date', 'diesel consumtion', 'remarks'],
            ['', 'carmen', 'ABC-1234', '50', '2026-03-01', '12', 'ok'],
            ['', 'carmen', $unknown, '50', '2026-03-02', '', 'bad plate'],
        ]);

        $importer = app(DailyCollectionImporter::class)->forUser($this->staff->id);
        $log = $importer->run($path, preview: false, userId: $this->staff->id, originalName: 'daily.xlsx');

        $this->assertSame(1, $log->imported);
        $this->assertSame(1, $log->skipped);

        $due = DailyDue::where('member_id', $member->id)->first();
        $this->assertNotNull($due);
        $this->assertSame(1, $due->ticket_quantity);
        $this->assertSame('2026-03-01', $due->collection_date->toDateString());

        // The system assigned a ticket number itself.
        $this->assertNotNull($due->ticket_number);
        $this->assertDatabaseHas('tickets', ['ticket_number' => $due->ticket_number, 'status' => 'used', 'daily_due_id' => $due->id]);
        $this->assertDatabaseHas('fuel_consumptions', ['member_id' => $member->id, 'liters' => 12]);
        $this->assertStringContainsString('No member matches', $log->errors[0]['message']);
    }

    public function test_daily_collection_derives_the_ticket_count_from_the_amount_paid(): void
    {
        $member = Member::factory()->create(['plate_number' => 'ABC-1234', 'route' => 'Carmen']);

        $path = $this->xlsx([
            ['plate number', 'total amount', 'date'],
            ['ABC-1234', '200', '2026-03-01'], // ₱200 -> 4 tickets
        ]);

        app(DailyCollectionImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'amt.xlsx');

        $due = DailyDue::where('member_id', $member->id)->firstOrFail();
        $this->assertSame(4, $due->ticket_quantity);
        $this->assertEqualsWithDelta(200.0, (float) $due->amount_paid, 0.01);
        $this->assertSame(4, $due->tickets()->count());

        $raw = $due->tickets()->pluck('ticket_number')->all();
        // Formatted as (at least) 4 digits.
        foreach ($raw as $n) {
            $this->assertMatchesRegularExpression('/^\d{4,}$/', $n);
        }
        // Ticket numbers run sequentially.
        $numbers = collect($raw)->map(fn ($n) => (int) $n)->sort()->values()->all();
        $this->assertSame(range($numbers[0], $numbers[0] + 3), $numbers);
    }

    public function test_daily_collection_uses_an_explicit_number_of_tickets_column(): void
    {
        $member = Member::factory()->create(['plate_number' => 'ABC-1234', 'route' => 'Carmen']);

        $path = $this->xlsx([
            ['plate number', 'number of tickets', 'total amount', 'date'],
            ['ABC-1234', '3', '', '2026-03-01'], // amount blank -> derived from the ticket count
        ]);

        app(DailyCollectionImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'qty.xlsx');

        $due = DailyDue::where('member_id', $member->id)->firstOrFail();
        $this->assertSame(3, $due->ticket_quantity);
        $this->assertEqualsWithDelta(150.0, (float) $due->amount_paid, 0.01);
    }

    public function test_daily_collection_rejects_an_amount_that_is_not_a_multiple_of_the_ticket_price(): void
    {
        Member::factory()->create(['plate_number' => 'ABC-1234', 'route' => 'Carmen']);

        $path = $this->xlsx([
            ['plate number', 'total amount', 'date'],
            ['ABC-1234', '75', '2026-03-01'],
        ]);

        $log = app(DailyCollectionImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'odd.xlsx');

        $this->assertSame(0, $log->imported);
        $this->assertSame(1, $log->skipped);
        $this->assertStringContainsString('multiple of', $log->errors[0]['message']);
    }

    public function test_daily_collection_import_records_a_voluntary_alkansiya_contribution(): void
    {
        $member = Member::factory()->create(['plate_number' => 'ABC-1234', 'route' => 'Carmen', 'alkansiya_balance' => 0, 'savings_balance' => 0]);

        $path = $this->xlsx([
            ['plate number', 'total amount', 'date', 'alkansiya'],
            ['ABC-1234', '50', '2026-03-01', '40'],
        ]);

        app(DailyCollectionImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'alk.xlsx');

        $member->refresh();
        $this->assertEqualsWithDelta(40.0, (float) $member->alkansiya_balance, 0.01);
        $this->assertEqualsWithDelta(35.0, (float) $member->savings_balance, 0.01); // dues savings share only
        $this->assertDatabaseHas('alkansiya_contributions', ['member_id' => $member->id, 'amount' => 40]);
    }

    public function test_daily_collection_loan_payment_is_skipped_without_an_approved_loan(): void
    {
        $member = Member::factory()->create(['plate_number' => 'ABC-1234', 'route' => 'Carmen']);

        $path = $this->xlsx([
            ['plate number', 'total amount', 'date', 'loans'],
            ['ABC-1234', '50', '2026-03-01', '200'],
        ]);

        $log = app(DailyCollectionImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'loan.xlsx');

        $this->assertSame(0, $log->imported);
        $this->assertSame(1, $log->skipped);
        $this->assertStringContainsString('no approved loan', $log->errors[0]['message']);
        $this->assertDatabaseCount('daily_dues', 0);
        $this->assertDatabaseCount('loan_payments', 0);
    }

    public function test_daily_collection_loan_payment_posts_against_an_approved_loan(): void
    {
        $member = Member::factory()->create(['plate_number' => 'ABC-1234', 'route' => 'Carmen', 'savings_balance' => 500]);

        $loan = Loan::create([
            'member_id' => $member->id, 'type' => 'cash', 'amount' => 3000, 'interest_rate' => 0,
            'term_months' => 3, 'penalty_rate' => 2, 'loan_date' => now()->toDateString(), 'status' => 'approved',
        ]);
        app(LoanScheduleService::class)->generate($loan->fresh());

        $path = $this->xlsx([
            ['plate number', 'total amount', 'date', 'loans'],
            ['ABC-1234', '50', '2026-03-01', '500'],
        ]);

        $log = app(DailyCollectionImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'loan2.xlsx');

        $this->assertSame(1, $log->imported);
        $this->assertDatabaseHas('daily_dues', ['member_id' => $member->id]);
        $this->assertDatabaseHas('loan_payments', ['loan_id' => $loan->id, 'amount' => 500]);
        $this->assertEqualsWithDelta(2500.0, (float) $loan->fresh()->balance, 0.01);
        // Loan payment is cash — the member's savings balance is untouched.
        $this->assertEqualsWithDelta(535.0, (float) $member->fresh()->savings_balance, 0.01); // 500 + 35 dues share
        $this->assertDatabaseMissing('savings_ledger', ['source_type' => 'loan_deduction']);
    }

    public function test_daily_collection_preview_rolls_back_but_logs(): void
    {
        Member::factory()->create(['plate_number' => 'ABC-1234']);

        $path = $this->xlsx([
            ['plate number', 'total amount', 'date'],
            ['ABC-1234', '50', '2026-03-01'],
        ]);

        $log = app(DailyCollectionImporter::class)->forUser($this->staff->id)
            ->run($path, preview: true, userId: $this->staff->id, originalName: 'p.xlsx');

        $this->assertSame(1, $log->imported);
        $this->assertDatabaseCount('daily_dues', 0);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseHas('import_logs', ['preview' => true, 'imported' => 1]);
    }

    public function test_duplicate_member_date_in_file_is_skipped(): void
    {
        Member::factory()->create(['plate_number' => 'ABC-1234']);

        $path = $this->xlsx([
            ['plate number', 'total amount', 'date'],
            ['ABC-1234', '50', '2026-03-01'],
            ['ABC-1234', '50', '2026-03-01'],
        ]);

        $log = app(DailyCollectionImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'd.xlsx');

        $this->assertSame(1, $log->imported);
        $this->assertSame(1, $log->skipped);
        $this->assertDatabaseCount('daily_dues', 1);
    }

    public function test_expenses_import(): void
    {
        $path = $this->xlsx([
            ['name', 'date', 'particulars', 'amount', 'control number', 'remarks'],
            ['Meralco', '2026-02-15', 'Electricity', '3450.75', 'CN-001', 'Feb bill'],
            ['', '2026-02-16', '', '100', '', 'no particulars'],
        ]);

        $log = app(ExpensesImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'e.xlsx');

        $this->assertSame(1, $log->imported);
        $this->assertSame(1, $log->skipped);
        $this->assertDatabaseHas('income_expenses', [
            'type' => 'expense',
            'reference_no' => 'CN-001',
            'amount' => 3450.75,
            'description' => 'Electricity — Meralco',
        ]);
    }

    public function test_rental_import_creates_one_row_per_money_column(): void
    {
        $path = $this->xlsx([
            ['business name', 'date', 'route', 'rental amount', 'parking fee', 'dispatcher rental', 'rental type', 'remarks'],
            ['Lechon Manok', '2026-02-01', 'carmen', '1000', '0', '', 'Lechon Manok', ''],
            ['Barber Shop', '2026-02-01', 'lumbia', '320', '50', '', 'Barber Shop', ''],
        ]);

        $log = app(RentalImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'r.xlsx');

        $this->assertSame(2, $log->imported);
        $this->assertSame(0, $log->skipped);
        // Row 1 -> 1 income row; row 2 -> 2 income rows (rental + parking).
        $this->assertSame(3, IncomeExpense::where('type', 'income')->count());
        $this->assertDatabaseHas('income_expenses', ['category' => 'lechon_manok', 'amount' => 1000]);
        $this->assertDatabaseHas('income_expenses', ['category' => 'barber_shop', 'amount' => 320]);
        $this->assertDatabaseHas('income_expenses', ['category' => 'parking_fee', 'amount' => 50]);
    }

    public function test_rental_import_matches_the_real_template_shape(): void
    {
        // Real template: business name, date, route, rental amount, rental type, remarks
        $path = $this->xlsx([
            ['business name', 'date', 'route', 'rental amount', 'rental type', 'remarks'],
            ['Mackings Proben', '2026-02-03', '', '250', 'tricab rental', ''],
            ['Fruit Stand', '2026-02-03', 'carmen', '', '', 'no amount'],
        ]);

        $log = app(RentalImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'real.xlsx');

        $this->assertSame(1, $log->imported);
        $this->assertSame(1, $log->skipped);
        $this->assertDatabaseHas('income_expenses', [
            'type' => 'income',
            'category' => 'tricab_rental',
            'amount' => 250,
            'description' => 'Mackings Proben — tricab rental',
        ]);
    }

    public function test_missing_required_headers_is_rejected(): void
    {
        $path = $this->xlsx([['date', 'amount'], ['2026-01-01', '10']]);

        $this->expectException(\RuntimeException::class);
        app(ExpensesImporter::class)->forUser($this->staff->id)
            ->run($path, preview: false, userId: $this->staff->id, originalName: 'bad.xlsx');
    }

    public function test_import_page_and_upload_are_admin_only(): void
    {
        $this->actingAs($this->userWithRole('collector'))
            ->get(route('import.index'))->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('import.index'))->assertOk()->assertSee('Import Data');
    }

    public function test_upload_route_runs_the_importer_and_writes_a_log(): void
    {
        $path = $this->xlsx([
            ['date', 'particulars', 'amount', 'control number'],
            ['2026-04-01', 'Fuel for office generator', '1500', 'OR-77'],
        ]);

        $file = new File('expenses template.xlsx', fopen($path, 'r'));

        $this->actingAs($this->staff)
            ->post(route('import.store'), [
                'template' => 'expenses',
                'file' => $file,
                'preview' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('income_expenses', ['reference_no' => 'OR-77', 'amount' => 1500]);
        $this->assertDatabaseHas('import_logs', ['template' => 'expenses', 'imported' => 1, 'preview' => false]);
    }

    public function test_upload_rejects_non_xlsx(): void
    {
        $file = UploadedFile::fake()->create('data.csv', 10, 'text/csv');

        $this->actingAs($this->staff)
            ->post(route('import.store'), ['template' => 'expenses', 'file' => $file])
            ->assertSessionHasErrors('file');
    }
}
