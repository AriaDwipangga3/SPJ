<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Backup gagal dengan penyebab yang bisa dijelaskan ke admin
 * (mysqldump tidak ketemu, dump gagal, zip gagal, dst).
 */
class BackupFailedException extends RuntimeException
{
    public function __construct(string $message, public string $detail = '')
    {
        parent::__construct($message);
    }
}
