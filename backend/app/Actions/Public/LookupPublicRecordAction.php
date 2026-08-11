<?php

namespace App\Actions\Public;

use App\Models\Appointment;
use App\Models\DonationCard;
use App\Models\Donor;

/**
 * Privacy-safe public lookups.
 *
 * Both lookups require the reference and the phone number together, match the
 * reference case-insensitively, and compare against the stored normalised
 * phone. A miss returns null so callers can emit one generic message rather
 * than distinguishing "no such reference" from "wrong phone", which would make
 * donor enumeration easy.
 */
class LookupPublicRecordAction
{
    public function appointment(string $reference, string $phone): ?Appointment
    {
        return Appointment::query()
            ->with(['donor', 'centre'])
            ->whereRaw('UPPER(reference) = ?', [strtoupper(trim($reference))])
            ->whereHas('donor', fn ($query) => $query->where('phone_normalized', Donor::normalizePhone($phone)))
            ->first();
    }

    public function card(string $reference, string $phone): ?DonationCard
    {
        return DonationCard::query()
            ->with(['donor.donations'])
            ->whereRaw('UPPER(card_number) = ?', [strtoupper(trim($reference))])
            ->whereHas('donor', fn ($query) => $query->where('phone_normalized', Donor::normalizePhone($phone)))
            ->first();
    }
}
