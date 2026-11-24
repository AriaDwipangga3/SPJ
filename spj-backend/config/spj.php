<?php

return [
    'nomor_prefix' => env('SPJ_NOMOR_PREFIX', 'BPP'),
    'default_max_file_mb' => (int) env('SPJ_MAX_FILE_MB', 10),

    // Lokasi mysqldump untuk backup. Kosongkan untuk deteksi otomatis (Laragon/XAMPP/PATH).
    'mysqldump_path' => env('MYSQLDUMP_PATH'),
];