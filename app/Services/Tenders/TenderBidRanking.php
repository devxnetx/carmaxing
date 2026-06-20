<?php

namespace App\Services\Tenders;

use App\Enums\TenderBidStatus;
use App\Enums\TenderStatus;
use App\Models\Tender;
use App\Models\TenderBid;
use App\Models\User;
use Illuminate\Support\Collection;

class TenderBidRanking
{
    public function __construct(
        private TenderAnonymousBidHistory $bidHistory,
    ) {}

    /**
     * One row per bidder: their active offer, or best outbid if they were beaten.
     * The current leader (active bid) is always listed first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forTender(Tender $tender, ?User $viewer = null): array
    {
        $leadingBidId = $tender->leadingBidId();

        $statuses = $tender->status === TenderStatus::Awarded
            ? [TenderBidStatus::Won, TenderBidStatus::Lost]
            : [TenderBidStatus::Active, TenderBidStatus::Outbid];

        $bids = ($tender->relationLoaded('bids')
            ? $tender->bids->filter(fn (TenderBid $bid) => in_array($bid->status, $statuses, true))
            : $tender->bids()->whereIn('status', $statuses)->get())
            ->groupBy('user_id')
            ->map(fn (Collection $userBids) => $this->standingBidForUser($userBids))
            ->sortBy([
                fn (TenderBid $bid) => $leadingBidId !== null && (int) $bid->id === $leadingBidId ? 0 : 1,
                fn (TenderBid $bid) => -$bid->amount,
                fn (TenderBid $bid) => $bid->created_at->timestamp,
            ])
            ->values();

        $anonymousNumbers = $this->bidHistory->anonymousNumbersFor($tender);

        return $bids->map(function (TenderBid $bid) use ($anonymousNumbers, $viewer, $leadingBidId) {
            $number = (int) ($anonymousNumbers[$bid->user_id] ?? 1);
            $isYours = $viewer && (int) $bid->user_id === (int) $viewer->id;
            $isLeader = $leadingBidId !== null && (int) $bid->id === $leadingBidId;

            return [
                'id' => $bid->id,
                'anonymous_label' => $isYours
                    ? __('tenders.anonymous_you')
                    : __('tenders.anonymous_bidder', ['number' => $number]),
                'amount' => $bid->amount,
                'placed_at' => $bid->created_at->toIso8601String(),
                'placed_ago' => $bid->created_at->diffForHumans(),
                'status' => $bid->status->value,
                'is_leader' => $isLeader,
                'is_yours' => $isYours,
                'avatar_index' => $number % 8,
            ];
        })->all();
    }

    private function standingBidForUser(Collection $userBids): TenderBid
    {
        $active = $userBids->first(fn (TenderBid $bid) => $bid->status === TenderBidStatus::Active);

        if ($active) {
            return $active;
        }

        return $userBids->sortByDesc('amount')->first();
    }
}