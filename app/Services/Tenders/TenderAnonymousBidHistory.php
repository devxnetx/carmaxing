<?php

namespace App\Services\Tenders;

use App\Enums\TenderBidStatus;
use App\Models\Tender;
use App\Models\User;

class TenderAnonymousBidHistory
{
    /**
     * @return array<int, int>
     */
    public function anonymousNumbersFor(Tender $tender): array
    {
        return $tender->bids()
            ->selectRaw('user_id, MIN(created_at) as first_bid_at')
            ->groupBy('user_id')
            ->reorder()
            ->orderBy('first_bid_at')
            ->pluck('user_id')
            ->values()
            ->flip()
            ->map(fn ($index) => $index + 1)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forTender(Tender $tender, ?User $viewer = null): array
    {
        $bids = $tender->bids()
            ->whereNotIn('status', [TenderBidStatus::Revoked])
            ->orderByDesc('created_at')
            ->get();

        $anonymousNumbers = $this->anonymousNumbersFor($tender);
        $leadingBidId = $tender->leadingBidId();

        return $bids->map(function ($bid) use ($anonymousNumbers, $viewer, $leadingBidId) {
            $number = (int) ($anonymousNumbers[$bid->user_id] ?? 1);
            $isYours = $viewer && (int) $bid->user_id === (int) $viewer->id;
            $isLeader = $leadingBidId !== null
                && (int) $bid->id === $leadingBidId
                && in_array($bid->status, [TenderBidStatus::Active, TenderBidStatus::Won], true);

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
        })->values()->all();
    }
}