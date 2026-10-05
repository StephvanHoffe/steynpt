<?php

namespace App\Services;

use App\Models\ContactRequest;
use App\Models\User;
use App\Services\Plans\PipelineServer;

/** Tellers voor het beheer: wat wacht er op Steyn? */
final class AdminCounts
{
    /** @return array{training: int, voeding: int, requests: int, applied: int, rewards: int} */
    public static function get(): array
    {
        $pipeline = PipelineServer::load();
        // Schema's die op Steyn wachten: nog te maken of te controleren.
        $planAction = fn (array $c) => $c['wacht'] + $c['controleren'];

        return [
            'training' => $planAction($pipeline['counts']['training']),
            'voeding' => $planAction($pipeline['counts']['voeding']),
            'requests' => ContactRequest::query()->where('handled', false)->count(),
            'applied' => User::query()->where('coaching_status', 'aangevraagd')->count(),
            'rewards' => User::query()->where('coaching_status', 'actief')->whereNotNull('referred_by_id')->whereNull('referral_reward_at')->count(),
        ];
    }
}
