<?php

namespace App\Services\Import;

use App\Models\Member;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * members template.xlsx
 * headers: member no, first name, last name, middle name, plate number,
 *          operator name, route, category, contact number, address,
 *          date joined, status
 *
 * Each row -> one members row. First/last name, plate number and route are
 * required; everything else has a sensible default:
 *   - member no   : auto "MBR-####" when blank
 *   - operator name: the member's own full name when blank
 *   - category    : "member"
 *   - date joined : today
 *   - status      : "active"
 *
 * A plate number or member no that already exists (in the file or the
 * database) is skipped so re-uploading the same sheet is safe.
 */
class MembersImporter extends BaseImporter
{
    /** @var array<string, true> plate numbers already handled this file */
    private array $seenPlates = [];

    /** @var array<string, true> member numbers already handled this file */
    private array $seenNumbers = [];

    public function __construct(SpreadsheetReader $reader, private ?int $userId = null)
    {
        parent::__construct($reader);
    }

    public function forUser(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    protected function template(): string
    {
        return 'members';
    }

    protected function requiredHeaders(): array
    {
        return ['first name', 'last name', 'plate number', 'route'];
    }

    protected function handleRow(array $row, int $line): void
    {
        $firstname = $this->value($row, 'first name');
        $lastname = $this->value($row, 'last name');
        if ($firstname === '' || $lastname === '') {
            throw new RowSkipped('Missing first name or last name.');
        }

        $plate = Str::upper($this->value($row, 'plate number'));
        if ($plate === '') {
            throw new RowSkipped('Missing plate number.');
        }

        $route = $this->normaliseRoute($this->value($row, 'route'));
        if (! $route) {
            throw new RowSkipped('Route must be Carmen or Cogon.');
        }

        $plateKey = Str::lower($plate);
        if (isset($this->seenPlates[$plateKey])) {
            throw new RowSkipped("Plate \"{$plate}\" appears earlier in this file.");
        }
        if (Member::withTrashed()->where('plate_number', $plate)->exists()) {
            throw new RowSkipped("A member with plate \"{$plate}\" already exists.");
        }

        $memberNo = $this->value($row, 'member no') ?: $this->nextMemberNo();
        $numberKey = Str::lower($memberNo);
        if (isset($this->seenNumbers[$numberKey])) {
            throw new RowSkipped("Member no \"{$memberNo}\" appears earlier in this file.");
        }
        if (Member::withTrashed()->where('member_no', $memberNo)->exists()) {
            throw new RowSkipped("Member no \"{$memberNo}\" already exists.");
        }

        $this->seenPlates[$plateKey] = true;
        $this->seenNumbers[$numberKey] = true;

        Member::create([
            'member_no' => $memberNo,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'middlename' => $this->value($row, 'middle name') ?: null,
            'plate_number' => $plate,
            'operator_name' => $this->value($row, 'operator name') ?: trim("{$firstname} {$lastname}"),
            'route' => $route,
            'category' => $this->normaliseCategory($this->value($row, 'category')),
            'contact_number' => $this->value($row, 'contact number') ?: null,
            'address' => $this->value($row, 'address') ?: null,
            'date_joined' => $this->date($row, 'date joined') ?? Carbon::today()->toDateString(),
            'status' => $this->normaliseStatus($this->value($row, 'status')),
            'savings_balance' => 0,
        ]);
    }

    private function normaliseRoute(string $value): ?string
    {
        return match (Str::lower(trim($value))) {
            'carmen' => 'Carmen',
            'cogon' => 'Cogon',
            default => null,
        };
    }

    private function normaliseCategory(string $value): string
    {
        return match (Str::lower(trim($value))) {
            'non-member', 'nonmember', 'non member' => 'non-member',
            default => 'member',
        };
    }

    private function normaliseStatus(string $value): string
    {
        return match (Str::lower(trim($value))) {
            'inactive' => 'inactive',
            default => 'active',
        };
    }

    private function nextMemberNo(): string
    {
        do {
            $candidate = 'MBR-'.random_int(1000, 9999);
        } while (
            isset($this->seenNumbers[Str::lower($candidate)])
            || Member::withTrashed()->where('member_no', $candidate)->exists()
        );

        return $candidate;
    }
}
