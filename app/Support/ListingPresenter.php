<?php

namespace App\Support;

use App\Models\Listing;

class ListingPresenter
{
    public static function fuelLabel(?string $fuel): ?string
    {
        return match ($fuel) {
            'petrol' => __('messages.fuel_petrol'),
            'diesel' => __('messages.fuel_diesel'),
            'lpg' => __('messages.fuel_lpg'),
            'cng' => __('messages.fuel_cng'),
            'electric' => __('messages.fuel_electric'),
            'hybrid' => __('messages.fuel_hybrid'),
            'plug-in-hybrid' => __('messages.fuel_plug_in_hybrid'),
            default => $fuel,
        };
    }

    public static function transmissionLabel(?string $transmission): ?string
    {
        return match ($transmission) {
            'manual' => __('messages.transmission_manual'),
            'automatic' => __('messages.transmission_automatic'),
            'semi-automatic' => __('messages.transmission_semi'),
            default => $transmission,
        };
    }

    public static function drivetrainLabel(?string $drivetrain): ?string
    {
        return match ($drivetrain) {
            'fwd' => __('messages.drivetrain_fwd'),
            'rwd' => __('messages.drivetrain_rwd'),
            'awd', '4x4' => __('messages.drivetrain_4x4'),
            default => $drivetrain,
        };
    }

    public static function bodyLabel(?string $body): ?string
    {
        return match ($body) {
            'sedan' => __('messages.body_sedan'),
            'hatchback' => __('messages.body_hatchback'),
            'wagon' => __('messages.body_wagon'),
            'suv' => __('messages.body_suv'),
            'coupe' => __('messages.body_coupe'),
            'cabrio' => __('messages.body_cabrio'),
            'van' => __('messages.body_van'),
            'pickup' => __('messages.body_pickup'),
            default => $body,
        };
    }

    public static function registrationLabel(?string $type): ?string
    {
        return match ($type) {
            'permanent' => __('messages.registration_permanent'),
            'temporary' => __('messages.registration_temporary'),
            default => $type,
        };
    }

    public static function euroLabel(?string $euro): ?string
    {
        return $euro ? strtoupper($euro) : null;
    }

    public static function specLine(Listing $listing): string
    {
        $parts = array_filter([
            $listing->year,
            $listing->mileage ? number_format($listing->mileage).' '.__('messages.km') : null,
            self::fuelLabel($listing->fuel_type),
            $listing->engine_power_hp ? $listing->engine_power_hp.' '.__('messages.hp') : null,
            $listing->engine_displacement_cc ? $listing->engine_displacement_cc.' '.__('messages.cc') : null,
            self::transmissionLabel($listing->transmission),
            self::euroLabel($listing->euro_standard),
        ]);

        return implode(' · ', $parts);
    }
}