<?php

namespace App\Console\Commands;

use App\Services\MemberSanctionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('members:lift-expired-suspensions')]
#[Description('Restore suspended members to active once their suspension end date has passed')]
class LiftExpiredSuspensions extends Command
{
    public function handle(MemberSanctionService $sanctions): int
    {
        $count = $sanctions->liftExpired();

        $this->info("Reactivated {$count} member(s) whose suspension has ended.");

        return self::SUCCESS;
    }
}
