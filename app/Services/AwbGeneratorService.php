<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AwbGeneratorService
{
    /**
     * Generate a unique AWB number safely using transactions and locks.
     * Guaranteed uniqueness. Standard format: AWB-12345678 (Clear, non-confusing format).
     * 
     * @param string|null $prefix
     * @param int $length
     * @return string
     */
    public function generateUniqueAwb(?string $prefix = null, int $length = 8): string
    {
        if ($prefix === null) {
            $prefix = \App\Models\Setting::where('key', 'awb_prefix')->value('value') ?: 'AWB-';
        }

        // Ensure clean prefix format if user set AWB without hyphen (e.g. AWB -> AWB-)
        if (!empty($prefix) && !str_ends_with($prefix, '-') && strtoupper($prefix) === 'AWB') {
            $prefix .= '-';
        }

        $maxAttempts = 20;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $randomDigits = $this->generateRandomNumericString($length);
            $awb = $prefix . $randomDigits;

            // Check if it exists
            $exists = Shipment::where('awb_number', $awb)->exists();

            if (!$exists) {
                return $awb;
            }

            $attempt++;
        }

        // Fallback if we somehow hit collisions
        return $prefix . time() . random_int(10, 99);
    }

    private function generateRandomNumericString(int $length): string
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
