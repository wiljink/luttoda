<?php

namespace App\Http\Controllers;

use App\Models\DailyDue;
use App\Models\Member;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyDuesController extends Controller
{
    /**
     * Price per single ticket. Kept in one place so the total
     * (and the per-ticket savings/rebate/assoc split) always agree
     * with what the form shows.
     */
    private const PRICE_PER_TICKET = 50.00;

    public function index(Request $request)
    {
        $date = $request->get('date', today()->toDateString());

        $dues = DailyDue::with('member')
            ->onDate($date)
            ->when($request->route, fn($q) => $q->where('route', $request->route))
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
            ->orderByRaw("route = ? DESC", [$route])
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
            'remarks' => 'nullable|string',
        ]);

        $exists = DailyDue::where('member_id', $validated['member_id'])
            ->where('collection_date', $validated['collection_date'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'member_id' => 'A due has already been recorded for this member today.',
            ])->withInput();
        }

        $quantity = (int) $validated['ticket_quantity'];

        try {
            $result = DB::transaction(function () use ($validated, $quantity) {
                // Auto-assign the lowest-numbered available tickets that
                // match this route, or route-agnostic tickets (route is
                // null) if not enough match. lockForUpdate prevents two
                // concurrent submissions from grabbing the same tickets.
                $tickets = Ticket::where('status', 'available')
                    ->where(function ($q) use ($validated) {
                        $q->where('route', $validated['route'])
                          ->orWhereNull('route');
                    })
                    ->orderByRaw("route = ? DESC", [$validated['route']])
                    ->orderBy('ticket_number')
                    ->lockForUpdate()
                    ->limit($quantity)
                    ->get();

                if ($tickets->count() < $quantity) {
                    throw new \RuntimeException('NO_TICKETS_AVAILABLE');
                }

                $totalAmount = self::PRICE_PER_TICKET * $quantity;

                $dailyDue = DailyDue::create([
                    'member_id' => $validated['member_id'],
                    'collection_date' => $validated['collection_date'],
                    'route' => $validated['route'],
                    'remarks' => $validated['remarks'] ?? null,
                    'amount_paid' => $totalAmount,
                    'ticket_quantity' => $quantity,
                    'collected_by' => auth()->id(),
                ]);

                // Claim every locked ticket for this due.
                foreach ($tickets as $ticket) {
                    $ticket->update([
                        'status' => 'used',
                        'daily_due_id' => $dailyDue->id,
                        'used_on' => $validated['collection_date'],
                    ]);
                }

                // Sync the first ticket_number back onto the daily_dues row
                // so it still displays directly in listings/tables without
                // needing to join through the tickets relation.
                $dailyDue->update(['ticket_number' => $tickets->first()->ticket_number]);

                // Capture ticket numbers here, inside the transaction,
                // instead of relying on a $dailyDue->tickets relation
                // lookup afterward.
                return [
                    'dailyDue' => $dailyDue,
                    'ticketNumbers' => $tickets->pluck('ticket_number')->all(),
                ];
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'NO_TICKETS_AVAILABLE') {
                return back()->withErrors([
                    'route' => 'Not enough available tickets left for this route. Please add a new ticket booklet first.',
                ])->withInput();
            }

            throw $e;
        }

        $ticketNumbers = $result['ticketNumbers'];
        $ticketLabel = count($ticketNumbers) > 1
            ? ('#' . $ticketNumbers[0] . ' to #' . end($ticketNumbers))
            : ('#' . $ticketNumbers[0]);

        return redirect()->route('daily-dues.index')
            ->with('success', 'Collection recorded successfully. Ticket ' . $ticketLabel . ' assigned.');
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
                    if (!empty($newTicketNumber)) {
                        $newTicket = Ticket::where('ticket_number', $newTicketNumber)
                            ->where('status', 'available')
                            ->lockForUpdate()
                            ->first();

                        if (!$newTicket) {
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
        DB::transaction(function () use ($dailyDue) {
            // Release every ticket linked to this due back to available
            // before deleting the record.
            Ticket::where('daily_due_id', $dailyDue->id)->update([
                'status' => 'available',
                'daily_due_id' => null,
            ]);

            $dailyDue->delete(); // automatically adjusts savings_balance (model event)
        });

        return redirect()->route('daily-dues.index')
            ->with('success', 'Record deleted successfully.');
    }
}
