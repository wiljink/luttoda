<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->route, fn($q) => $q->where('route', $request->route))
            ->orderBy('route')
            ->orderBy('ticket_number')
            ->get();

        $availableCount = Ticket::where('status', 'available')->count();
        $usedCount = Ticket::where('status', 'used')->count();

        $ranges = $this->groupIntoRanges($tickets);

        return view('tickets.index', compact('ranges', 'availableCount', 'usedCount'));
    }

    /**
     * Collapse a flat ticket collection into contiguous ranges,
     * grouped by route + status. A range breaks whenever the numeric
     * part of the ticket number isn't consecutive, or route/status changes.
     */
    private function groupIntoRanges($tickets)
    {
        $ranges = [];
        $current = null;

        foreach ($tickets as $ticket) {
            // Extract the numeric portion for consecutive-number comparison
            preg_match('/(\d+)$/', $ticket->ticket_number, $matches);
            $numericValue = isset($matches[1]) ? (int) $matches[1] : null;

            $sameGroup = $current
                && $current['route'] === $ticket->route
                && $current['status'] === $ticket->status
                && $numericValue !== null
                && $current['last_numeric'] !== null
                && $numericValue === $current['last_numeric'] + 1;

            if ($sameGroup) {
                $current['to'] = $ticket->ticket_number;
                $current['last_numeric'] = $numericValue;
                $current['count']++;
                $current['ids'][] = $ticket->id;
                $current['used_on'] = $ticket->used_on ?? $current['used_on'];
            } else {
                if ($current) {
                    $ranges[] = $current;
                }

                $current = [
                    'from' => $ticket->ticket_number,
                    'to' => $ticket->ticket_number,
                    'route' => $ticket->route,
                    'status' => $ticket->status,
                    'count' => 1,
                    'ids' => [$ticket->id],
                    'last_numeric' => $numericValue,
                    'used_on' => $ticket->used_on,
                ];
            }
        }

        if ($current) {
            $ranges[] = $current;
        }

        return $ranges;
    }

    public function create()
    {
        return view('tickets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'route' => 'nullable|in:Carmen,Cogon',
            'prefix' => 'nullable|string|max:10',
            'from_number' => 'required|integer|min:1',
            'to_number' => 'required|integer|gte:from_number',
            'padding' => 'required|integer|min:1|max:10',
        ]);

        $from = $validated['from_number'];
        $to = $validated['to_number'];
        $padding = $validated['padding'];
        $prefix = $validated['prefix'] ?? '';
        $route = $validated['route'] ?? null;

        $rangeSize = $to - $from + 1;

        // Safety cap so a typo (e.g. 1 to 999999) can't lock up the request
        if ($rangeSize > 5000) {
            return back()->withErrors([
                'to_number' => 'Range too large (max 5000 tickets at a time). Please split into smaller batches.',
            ])->withInput();
        }

        // Build all ticket numbers first so we can check for duplicates
        // BEFORE inserting anything (avoids partial inserts on failure).
        $ticketNumbers = [];
        for ($i = $from; $i <= $to; $i++) {
            $ticketNumbers[] = $prefix . str_pad($i, $padding, '0', STR_PAD_LEFT);
        }

        $existing = Ticket::whereIn('ticket_number', $ticketNumbers)->pluck('ticket_number');

        if ($existing->isNotEmpty()) {
            return back()->withErrors([
                'from_number' => 'Some ticket numbers in this range already exist: ' .
                    $existing->take(5)->implode(', ') .
                    ($existing->count() > 5 ? ' (+' . ($existing->count() - 5) . ' more)' : ''),
            ])->withInput();
        }

        DB::transaction(function () use ($ticketNumbers, $route) {
            $now = now();
            $rows = array_map(fn($number) => [
                'ticket_number' => $number,
                'route' => $route,
                'status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ], $ticketNumbers);

            // Chunk inserts to avoid one giant query on large batches
            foreach (array_chunk($rows, 500) as $chunk) {
                Ticket::insert($chunk);
            }
        });

        return redirect()->route('tickets.index')
            ->with('success', count($ticketNumbers) . ' tickets added successfully (' .
                $ticketNumbers[0] . ' – ' . end($ticketNumbers) . ').');
    }

    public function edit(Ticket $ticket)
    {
        return view('tickets.edit', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'ticket_number' => 'required|string|max:20|unique:tickets,ticket_number,' . $ticket->id,
            'route' => 'nullable|in:Carmen,Cogon',
            'status' => 'required|in:available,used',
        ]);

        // Prevent flipping status away from 'used' behind the back of a
        // linked daily_due record — that relationship should stay in sync
        // via the DailyDuesController instead.
        if ($ticket->daily_due_id) {
            $validated['status'] = $ticket->status;
        }

        $ticket->update($validated);

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket updated successfully.');
    }

    public function editRange(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => 'required|string',
            'from' => 'required|string',
            'to' => 'required|string',
            'route' => 'nullable|in:Carmen,Cogon',
            'count' => 'required|integer',
        ]);

        // 'route' may be entirely absent from the request when its value
        // is null (Laravel's route() helper drops null query params), so
        // don't rely on $validated having the key — default it explicitly.
        $validated['route'] = $validated['route'] ?? null;

        return view('tickets.edit-range', $validated);
    }

    /**
     * Update the route for every ticket in a range at once.
     * Ticket numbers and status aren't editable here since each
     * ticket in the range has a distinct number.
     */
    public function bulkUpdateRoute(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => 'required|string',
            'route' => 'nullable|in:Carmen,Cogon',
        ]);

        $ids = array_filter(explode(',', $validated['ticket_ids']));

        $updated = Ticket::whereIn('id', $ids)->update(['route' => $validated['route'] ?? null]);

        return redirect()->route('tickets.index')
            ->with('success', $updated . ' tickets updated.');
    }

    public function destroy(Ticket $ticket)
    {
        if ($ticket->daily_due_id) {
            return redirect()->route('tickets.index')
                ->with('error', 'Cannot delete ticket ' . $ticket->ticket_number . ' — it is linked to a recorded collection. Delete or edit that record first.');
        }

        $ticket->delete();

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket ' . $ticket->ticket_number . ' deleted.');
    }

    /**
     * Delete every ticket in a range (submitted as comma-separated IDs
     * from the range row). Refuses the whole batch if any ticket in
     * the range is used or linked, so a partial delete can't silently
     * orphan a daily_due record.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => 'required|string',
        ]);

        $ids = array_filter(explode(',', $validated['ticket_ids']));

        $blocked = Ticket::whereIn('id', $ids)
            ->where(function ($q) {
                $q->where('status', 'used')->orWhereNotNull('daily_due_id');
            })
            ->exists();

        if ($blocked) {
            return redirect()->route('tickets.index')
                ->with('error', 'Cannot delete this range — it contains tickets that are already used or linked to a recorded collection.');
        }

        $deleted = Ticket::whereIn('id', $ids)->delete();

        return redirect()->route('tickets.index')
            ->with('success', $deleted . ' tickets deleted.');
    }
}
