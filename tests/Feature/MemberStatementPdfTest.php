<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Services\AlkansiyaService;
use App\Services\Reports\LuttodaReportService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberStatementPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_statement_pdf_downloads_for_accounting_and_is_blocked_for_collectors(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->userWithRole('collector'))
            ->get(route('reports.member.statement.pdf', $member))
            ->assertForbidden();

        $response = $this->actingAs($this->userWithRole('accounting'))
            ->get(route('reports.member.statement.pdf', ['member' => $member, 'year' => 2026]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_statement_data_includes_the_alkansiya_figures(): void
    {
        $member = Member::factory()->create();
        app(AlkansiyaService::class)->record($member, '2026-05-01', 75);

        $data = app(LuttodaReportService::class)->memberStatementOfAccount($member->id, 2026);

        $this->assertEqualsWithDelta(75.0, $data['alkansiya_total'], 0.01);
        $this->assertEqualsWithDelta(75.0, $data['alkansiya_balance'], 0.01);
        $this->assertCount(1, $data['alkansiya_entries']);
    }
}
