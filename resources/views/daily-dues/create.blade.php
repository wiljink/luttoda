@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('daily-dues.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Dues Collection</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Record Daily Dues Collection</h1>
</div>

<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 max-w-xl">
    @if ($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('daily-dues.store') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Select Member *</label>
            <select name="member_id" required class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
                <option value="">-- Select Driver / Member --</option>
                @foreach($members as $member)
                    <option value="{{ $member->id }}" {{ old('member_id') == $member->id ? 'selected' : '' }}>
                        {{ $member->full_name }} ({{ $member->plate_number }} - {{ $member->route }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Collection Date *</label>
                <input type="date" name="collection_date" value="{{ old('collection_date', today()->toDateString()) }}" required class="w-full rounded border-gray-300 p-2 border">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Collection Route *</label>
                <select name="route" id="routeSelect" required class="w-full rounded border-gray-300 p-2 border">
                    <option value="Carmen" data-next-ticket="{{ $nextTickets['Carmen'] ?? '' }}" {{ old('route') === 'Carmen' ? 'selected' : '' }}>Carmen</option>
                    <option value="Cogon" data-next-ticket="{{ $nextTickets['Cogon'] ?? '' }}" {{ old('route') === 'Cogon' ? 'selected' : '' }}>Cogon</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Number of Tickets to Buy *</label>
            <input type="number" name="ticket_quantity" id="ticketQuantity" min="1" step="1"
                   value="{{ old('ticket_quantity', 1) }}" required
                   class="w-full rounded border-gray-300 p-2 border focus:ring focus:ring-blue-200">
            <p class="text-xs text-gray-400 mt-1">Ilisda kung pila ka ticket ang paliton sa maong route (₱50 kada ticket).</p>
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded p-3">
            <p class="text-sm font-semibold text-blue-700">
                🎫 Ticket(s) to be assigned: <span id="nextTicketDisplay">—</span>
            </p>
            <p class="text-xs text-blue-600 mt-1">
                The system will automatically use this ticket number (or range) for the selected route once you submit.
                You don't need to pick one manually.
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Total Amount</label>
            <input type="text" id="totalAmountDisplay" disabled value="₱50.00" class="w-full rounded bg-gray-50 border-gray-300 text-gray-500 p-2 border font-bold">
            <p class="text-xs text-gray-400 mt-1">Automatically distributed per ticket: ₱35 (Savings), ₱7.50 (Rebate), ₱7.50 (Assoc. Share).</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks / Notes</label>
            <textarea name="remarks" rows="2" class="w-full rounded border-gray-300 p-2 border">{{ old('remarks') }}</textarea>
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded shadow transition">
                Submit and Add to Savings
            </button>
        </div>
    </form>
</div>

<script>
    const PRICE_PER_TICKET = 50;

    function updateNextTicketPreview() {
        const routeSelect = document.getElementById('routeSelect');
        const qtyInput = document.getElementById('ticketQuantity');
        const display = document.getElementById('nextTicketDisplay');
        const totalDisplay = document.getElementById('totalAmountDisplay');

        const selectedOption = routeSelect.options[routeSelect.selectedIndex];
        const nextTicket = parseInt(selectedOption.getAttribute('data-next-ticket'), 10);
        let qty = parseInt(qtyInput.value, 10);

        if (!qty || qty < 1) {
            qty = 1;
        }

        if (nextTicket && !isNaN(nextTicket)) {
            if (qty > 1) {
                display.textContent = '#' + nextTicket + ' to #' + (nextTicket + qty - 1);
            } else {
                display.textContent = '#' + nextTicket;
            }
            display.classList.remove('text-red-600');
            display.classList.add('text-blue-700');
        } else {
            display.textContent = 'No tickets available for this route';
            display.classList.remove('text-blue-700');
            display.classList.add('text-red-600');
        }

        const total = qty * PRICE_PER_TICKET;
        totalDisplay.value = '₱' + total.toFixed(2);
    }

    document.getElementById('routeSelect').addEventListener('change', updateNextTicketPreview);
    document.getElementById('ticketQuantity').addEventListener('input', updateNextTicketPreview);
    document.addEventListener('DOMContentLoaded', updateNextTicketPreview);
</script>
@endsection
