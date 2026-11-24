<?php

namespace App\Helpers;

class Terbilang
{
    public static function convert(int $angka): string
    {
        $angka = abs($angka);
        $baca = [
            '', 'satu', 'dua', 'tiga', 'empat', 'lima',
            'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas',
        ];

        if ($angka < 12) {
            return $baca[$angka];
        }

        if ($angka < 20) {
            return self::convert($angka - 10) . ' belas';
        }

        if ($angka < 100) {
            return self::convert(intdiv($angka, 10)) . ' puluh ' . self::convert($angka % 10);
        }

        if ($angka < 200) {
            return 'seratus ' . self::convert($angka - 100);
        }

        if ($angka < 1000) {
            return self::convert(intdiv($angka, 100)) . ' ratus ' . self::convert($angka % 100);
        }

        if ($angka < 2000) {
            return 'seribu ' . self::convert($angka - 1000);
        }

        if ($angka < 1000000) {
            return self::convert(intdiv($angka, 1000)) . ' ribu ' . self::convert($angka % 1000);
        }

        if ($angka < 1000000000) {
            return self::convert(intdiv($angka, 1000000)) . ' juta ' . self::convert($angka % 1000000);
        }

        if ($angka < 1000000000000) {
            return self::convert(intdiv($angka, 1000000000)) . ' miliar ' . self::convert($angka % 1000000000);
        }

        return self::convert(intdiv($angka, 1000000000000)) . ' triliun ' . self::convert($angka % 1000000000000);
    }
}