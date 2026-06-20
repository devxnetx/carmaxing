<?php

namespace App\Services\Tenders;

use App\Models\Tender;
use App\Models\User;
use App\Support\TenderRules;

class TenderStatePresenter
{
    public function __construct(
        private TenderAnonymousBidHistory $bidHistory,
        private TenderBidRanking $bidRanking,
    ) {}

    public function forPoll(Tender $tender, ?User $viewer = null): array
    {
        $tender->refresh();

        $activeHigh = $tender->activeHighBidAmount();

        $myBid = null;

        if ($viewer) {
            $activeBid = $tender->bids()
                ->where('user_id', $viewer->id)
                ->where('status', \App\Enums\TenderBidStatus::Active)
                ->first();

            if ($activeBid) {
                $myBid = [
                    'id' => $activeBid->id,
                    'amount' => $activeBid->amount,
                    'revocable' => $activeBid->isRevocable(),
                ];
            }
        }

        $secondsRemaining = $tender->secondsRemaining();
        $finalDaySeconds = config('tenders.final_day_hours') * 3600;

        return [
            'status' => $tender->status->value,
            'ends_at' => $tender->ends_at->toIso8601String(),
            'seconds_remaining' => $secondsRemaining,
            'is_biddable' => $tender->isBiddable(),
            'current_high_bid' => $activeHigh,
            'leading_bid_id' => $tender->leadingBidId(),
            'starting_price' => $tender->starting_price,
            'minimum_next_bid' => $tender->minimumNextBidAmount(),
            'bid_increment' => $tender->bid_increment,
            'bid_count' => $tender->bid_count,
            'my_bid' => $myBid,
            'poll_interval_ms' => $secondsRemaining <= $finalDaySeconds
                ? config('tenders.poll_interval_final_day_ms')
                : config('tenders.poll_interval_ms'),
            'bid_ranking' => $this->bidRanking->forTender($tender, $viewer),
            'bid_history' => $this->bidHistory->forTender($tender, $viewer),
            'rules_accepted' => TenderRules::userHasAccepted($viewer),
        ];
    }
}