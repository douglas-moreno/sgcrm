<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

final class PipelineStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['slug' => PipelineStage::NEW_LEAD, 'name' => 'New Lead', 'position' => 1, 'is_terminal' => false, 'is_won' => false, 'is_lost' => false],
            ['slug' => PipelineStage::CONTACTED, 'name' => 'Contacted', 'position' => 2, 'is_terminal' => false, 'is_won' => false, 'is_lost' => false],
            ['slug' => PipelineStage::PROPOSAL_SENT, 'name' => 'Proposal Sent', 'position' => 3, 'is_terminal' => false, 'is_won' => false, 'is_lost' => false],
            ['slug' => PipelineStage::NEGOTIATION, 'name' => 'Negotiation', 'position' => 4, 'is_terminal' => false, 'is_won' => false, 'is_lost' => false],
            ['slug' => PipelineStage::WON, 'name' => 'Won', 'position' => 90, 'is_terminal' => true, 'is_won' => true, 'is_lost' => false],
            ['slug' => PipelineStage::LOST, 'name' => 'Lost', 'position' => 99, 'is_terminal' => true, 'is_won' => false, 'is_lost' => true],
        ];

        foreach ($stages as $row) {
            PipelineStage::updateOrCreate(['slug' => $row['slug']], $row + ['is_active' => true]);
        }
    }
}
