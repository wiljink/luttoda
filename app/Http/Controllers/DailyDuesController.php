<?php

namespace App\Http\Controllers;

use App\Exceptions\DailyDueException;
use App\Models\DailyDue;
use App\Models\Member;
use App\Models\Ticket;
use App\Services\DailyDueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyDuesController extends Controller
{
    public function __construct(private DailyDueService $dailyDues) {}

    public function index(Request $request)
    {
        $date = $request->get('date');

        // No date picked: show today, but if today has nothing yet fall back
        // to the most recent day that does (e.g. right after importing a
        // back-dated collection sheet) so the list isn't mysteriously empty.
        if (! $date) {
            $date = today()->toDateString();
            if (! DailyDue::onDate($date)->exists()) {
                $date = DailyDue::max('collection_date')
                    ? \Illuminate\Support\Carbon::parse(DailyDue::max('collection_date'))->toDateString()
                    : $date;
            }
        }

        $dues = DailyDue::with(['member', 'collector'])
            ->onDate($date)
            ->when($request->route, fn ($q) => $q->where('route', $request->route))
            ->latest()
            ->paginate(30);

        $totalToday = DailyDue::onDate($date)->sum('amount_paid');

        return view('daily-dues.index', compact('dues', 'date', 'totalToday'));
    }

    public function create()
    {
        $members = Member::active()->orderBy('firstname')->get();

        // Preview which ticket number will be auto-assigned per route,
        // so the form can show it before the user submits.
        $nextTickets = [
            'Carmen' => $this->peekNextTicket('Carmen'),
            'Cogon' => $this->peekNextTicket('Cogon'),
        ];

        return view('daily-dues.create', compact('members', 'nextTickets'));
    }

    /**
     * Read-only lookup of which ticket would be assigned next for a
     * given route, without locking or reserving it. Purely for display.
     */
    private function peekNextTicket(string $route): ?string
    {
        $ticket = Ticket::where('status', 'available')
            ->where(function ($q) use ($route) {
                $q->where('route', $route)
                    ->orWhereNull('route');
            })
            ->orderByRaw('route = ? DESC', [$route])
            ->orderBy('ticket_number')
            ->first();

        return $ticket?->ticket_number;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'collection_date' => 'required|date',
            'route' => 'required|in:Carmen,Cogon',
            'ticket_quantity' => 'required|integer|min:1|max:100',
            'alkansiya' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        try {
            $result = $this->dailyDues->recordWithAutoTickets([
                'member_id' => (int) $validated['member_id'],
                'collection_date' => $validated['collection_date'],
                'route' => $validated['route'],
                'ticket_quantity' => (int) $validated['ticket_quantity'],
                'alkansiya' => isset($validated['alkansiya']) ? (float) $validated['alkansiya'] : null,
                'remarks' => $validated['remarks'] ?? null,
                'collected_by' => auth()->id(),
            ]);
        } catch (DailyDueException $e) {
            return back()->withErrors(['member_id' => $e->getMessage()])->withInput();
        }

        $ticketNumbers = $result['ticket_numbers'];
        $ticketLabel = count($ticketNumbers) > 1
            ? ('#'.$ticketNumbers[0].' to #'.end($ticketNumbers))
            : ('#'.$ticketNumbers[0]);

        return redirect()->route('daily-dues.index')
            ->with('success', 'Collection recorded successfully. Ticket '.$ticketLabel.' assigned.');
    }

    public function show(DailyDue $dailyDue)
    {
        return view('daily-dues.show', compact('dailyDue'));
    }

    public function edit(DailyDue $dailyDue)
    {
        $availableTickets = Ticket::available()->orderBy('ticket_number')->get();

        return view('daily-dues.edit', compact('dailyDue', 'availableTickets'));
    }

    public function update(Request $request, DailyDue $dailyDue)
    {
        $validated = $request->validate([
            'ticket_number' => 'nullable|string|exists:tickets,ticket_number',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated, $dailyDue) {
                $newTicketNumber = $validated['ticket_number'] ?? null;
                $currentTicket = $dailyDue->ticket;

                // Lock current ticket row (if any) before touching it
                if ($currentTicket) {
                    $currentTicket = Ticket::where('id', $currentTicket->id)
                        ->lockForUpdate()
                        ->first();
                }

                $isChanging = $currentTicket?->ticket_number !== $newTicketNumber;

                if ($isChanging) {
                    // Release the old ticket
                    if ($currentTicket) {
                        $currentTicket->update([
                            'status' => 'available',
                            'daily_due_id' => null,
                        ]);
                    }

                    // Lock and claim the new ticket
                    if (! empty($newTicketNumber)) {
                        $newTicket = Ticket::where('ticket_number', $newTicketNumber)
                            ->where('status', 'available')
                            ->lockForUpdate()
                            ->first();

                        if (! $newTicket) {
                            throw new \RuntimeException('TICKET_TAKEN');
                        }

                        $newTicket->update([
                            'status' => 'used',
                            'daily_due_id' => $dailyDue->id,
                        ]);
                    }
                }

                // Keep daily_dues.ticket_number in sync with whatever
                // ticket is now actually linked (or null if removed).
                $validated['ticket_number'] = $newTicketNumber;

                $dailyDue->update($validated);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'TICKET_TAKEN') {
                return back()->withErrors([
                    'ticket_number' => 'This ticket has already been used. Please pick another.',
                ])->withInput();
            }

            throw $e;
        }

        return redirect()->route('daily-dues.index')
            ->with('success', 'Collection details updated successfully.');
    }

    public function destroy(DailyDue $dailyDue)
    {
        $this->dailyDues->reverse($dailyDue);

        return redirect()->route('daily-dues.index')
            ->with('success', 'Record deleted successfully.');
    }
}
