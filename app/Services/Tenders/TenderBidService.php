<?php

namespace App\Services\Tenders;

use App\Enums\TenderBidStatus;
use App\Models\Tender;
use App\Models\TenderBid;
use App\Models\User;
use App\Support\TenderRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenderBidService
{
    public function place(Tender $tender, User $user, int $amount): TenderBid
    {
        if (! $tender->isBiddable()) {
            throw ValidationException::withMessages([
                'amount' => __('tenders.not_biddable'),
            ]);
        }

        if ($tender->isOwnedBy($user)) {
            throw ValidationException::withMessages([
                'amount' => __('tenders.cannot_bid_own'),
            ]);
        }

        if (! TenderRules::userHasAccepted($user)) {
            throw ValidationException::withMessages([
                'amount' => __('tenders.rules_not_accepted'),
            ]);
        }

        $this->assertValidBidAmount($tender, $amount);

        return DB::transaction(function () use ($tender, $user, $amount) {
            $locked = Tender::query()->lockForUpdate()->findOrFail($tender->id);

            if (! $locked->isBiddable()) {
                throw ValidationException::withMessages([
                    'amount' => __('tenders.not_biddable'),
                ]);
            }

            $this->assertValidBidAmount($locked, $amount);

            $existingActive = TenderBid::query()
                ->where('tender_id', $locked->id)
                ->where('user_id', $user->id)
                ->where('status', TenderBidStatus::Active)
                ->lockForUpdate()
                ->first();

            if ($existingActive) {
                throw ValidationException::withMessages([
                    'amount' => __('tenders.already_has_active_bid'),
                ]);
            }

            $previousHigh = TenderBid::query()
                ->where('tender_id', $locked->id)
                ->where('status', TenderBidStatus::Active)
                ->orderByDesc('amount')
                ->lockForUpdate()
                ->first();

            if ($previousHigh) {
                $previousHigh->update(['status' => TenderBidStatus::Outbid]);
            }

            $bid = TenderBid::query()->create([
                'tender_id' => $locked->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'status' => TenderBidStatus::Active,
            ]);

            $locked->update([
                'current_high_bid_amount' => $amount,
                'bid_count' => (int) $locked->bid_count + 1,
            ]);

            return $bid;
        });
    }

    public function revoke(TenderBid $bid, User $user): void
    {
        if ((int) $bid->user_id !== (int) $user->id) {
            abort(403);
        }

        $bid->load('tender');

        if (! $bid->isRevocable()) {
            throw ValidationException::withMessages([
                'bid' => __('tenders.bid_not_revocable'),
            ]);
        }

        DB::transaction(function () use ($bid) {
            $lockedBid = TenderBid::query()->lockForUpdate()->findOrFail($bid->id);
            $tender = Tender::query()->lockForUpdate()->findOrFail($lockedBid->tender_id);

            if (! $lockedBid->status->isActive() || ! $tender->isBiddable()) {
                throw ValidationException::withMessages([
                    'bid' => __('tenders.bid_not_revocable'),
                ]);
            }

            $lockedBid->update([
                'status' => TenderBidStatus::Revoked,
                'revoked_at' => now(),
            ]);

            $tender->syncHighBidCache();
        });
    }

    public function award(Tender $tender, TenderBid $bid): void
    {
        if (! $tender->isOwnedBy(auth()->user())) {
            abort(403);
        }

        if ($tender->status !== \App\Enums\TenderStatus::Ended) {
            throw ValidationException::withMessages([
                'bid' => __('tenders.cannot_award_yet'),
            ]);
        }

        if ((int) $bid->tender_id !== (int) $tender->id || ! $bid->status->isActive()) {
            throw ValidationException::withMessages([
                'bid' => __('tenders.invalid_bid'),
            ]);
        }

        if (! $tender->meetsMinimumPrice($bid->amount)) {
            throw ValidationException::withMessages([
                'bid' => __('tenders.below_minimum_price'),
            ]);
        }

        DB::transaction(function () use ($tender, $bid) {
            $locked = Tender::query()->lockForUpdate()->findOrFail($tender->id);

            TenderBid::query()
                ->where('tender_id', $locked->id)
                ->where('status', TenderBidStatus::Active)
                ->where('id', '!=', $bid->id)
                ->update(['status' => TenderBidStatus::Lost]);

            $bid->update(['status' => TenderBidStatus::Won]);

            $locked->update([
                'status' => \App\Enums\TenderStatus::Awarded,
                'winning_bid_id' => $bid->id,
                'awarded_at' => now(),
            ]);
        });
    }

    private function assertValidBidAmount(Tender $tender, int $amount): void
    {
        $minimum = $tender->minimumNextBidAmount();
        $increment = (int) $tender->bid_increment;

        if ($amount < $minimum) {
            throw ValidationException::withMessages([
                'amount' => __('tenders.bid_too_low', ['min' => number_format($minimum)]),
            ]);
        }

        if (! $tender->isValidBidAmount($amount)) {
            throw ValidationException::withMessages([
                'amount' => __('tenders.bid_increment_invalid', ['step' => number_format($increment)]),
            ]);
        }
    }
}