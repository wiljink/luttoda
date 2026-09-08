<?php

namespace Tests\Feature;

use App\Models\IncomeExpense;
use App\Models\User;
use App\Services\Reports\LuttodaReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionsIncomeReportTest extends TestCase
{
    use RefreshDatabase;

    private function income(string $category, float $amount, string $date): void
    {
        IncomeExpense::create([
            'transaction_date' => $date,
            'type' => 'income',
            'category' => $category,
            'description' => "$category entry",
            'amount' => $amount,
            'recorded_by' => User::factory()->create()->id,
        ]);
    }

    public function test_groups_income_by_category_with_subtotals(): void
    {
        $this->income('stall_rental', 1000, '2026-09-01');
        $this->income('stall_rental', 800, '2026-09-05');
        $this->income('dispatcher_rental', 320, '2026-09-02');
        $this->income('parking_fee', 450, '2026-09-03');
        // expense + out-of-range income must not count
        IncomeExpense::create(['transaction_date' => '2026-09-02', 'type' => 'expense', 'category' => 'other_expense', 'description' => 'x', 'amount' => 999, 'recorded_by' => User::factory()->create()->id]);
        $this->income('stall_rental', 5000, '2026-10-01');

        $report = app(LuttodaReportService::class)->collectionsIncomeReport('2026-09-01', '2026-09-30');

        $this->assertSame(2570.0, $report['grand_total']);
        $this->assertSame(4, $report['transaction_count']);

        $byCat = collect($report['by_category'])->keyBy('category');
        $this->assertSame(1800.0, $byCat['stall_rental']['total']);
        $this->assertSame(2, $byCat['stall_rental']['count']);
        $this->assertSame(320.0, $byCat['dispatcher_rental']['total']);
        // sorted by total desc
        $this->assertSame('stall_rental', $report['by_category'][0]['category']);
    }

    public function test_page_renders_and_is_gated(): void
    {
        $this->income('dispatcher_rental', 320, now()->toDateString());

        $this->actingAs($this->userWithRole('collector'))
            ->get(route('reports.collections-income.page'))->assertForbidden();

        $this->actingAs($this->userWithRole('accounting'))
            ->get(route('reports.collections-income.page'))
            ->assertOk()
            ->assertSee('Collections / Rental Income')
            ->assertSee('Dispatcher Rental');
    }

    public function test_json_endpoint_omits_the_detail_rows(): void
    {
        $this->income('parking_fee', 100, now()->toDateString());

        $this->actingAs($this->admin())
            ->getJson(route('reports.collections-income'))
            ->assertOk()
            ->assertJsonMissingPath('transactions')
            ->assertJsonPath('grand_total', 100);
    }
}
