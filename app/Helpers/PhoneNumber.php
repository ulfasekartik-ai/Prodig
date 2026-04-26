<?php

namespace App\Helpers;

class PhoneNumber
{
    /**
     * Normalisasi nomor WhatsApp Indonesia ke format 62xxxxxxxxxx.
     *
     * Aturan:
     *  - Strip semua karakter non-digit (spasi, tanda hubung, plus, dll).
     *  - Jika diawali "0"  -> "62" + sisa.
     *  - Jika diawali "62" -> tetap.
     *  - Jika diawali "8"  -> "62" + sisa (anggap nomor lokal tanpa prefix).
     *  - Selain itu kembalikan apa adanya (digits) tanpa modifikasi prefix.
     *  - Input null/kosong/tidak mengandung digit -> null.
     */
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $input);
        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return $digits;
    }
}
