<?php

namespace Database\Seeders;

use App\Models\TicketType;
use Illuminate\Database\Seeder;

class TicketTypeSeeder extends Seeder
{
    public function run(): void
    {
        TicketType::updateOrCreate(['code' => TicketType::RETAILER], ['name' => 'Retailer']);
        TicketType::updateOrCreate(['code' => TicketType::CUSTOMER], ['name' => 'Customer']);
    }
}
