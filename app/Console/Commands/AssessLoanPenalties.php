<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Services\LoanPenaltyService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('loans:assess-penalties')]
#[Description('Recompute overdue penalties for every active loan')]
class AssessLoanPenalties extends Command
{
    public function handle(LoanPenaltyService $penaltyService)
    {
        Loan::active()->with('schedules')->chunkById(50, function ($loans) use ($penaltyService) {
            foreach ($loans as $loan) {
                $penaltyService->assess($loan);
            }
        });

        $this->info('Loan penalties reassessed.');
    }
}
