<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 1000; $i++) {
            Ticket::create([
                'ticket_number' => str_pad($i, 4, '0', STR_PAD_LEFT), // 0001, 0002, ...
                'status' => 'available',
            ]);
        }
    }
}
