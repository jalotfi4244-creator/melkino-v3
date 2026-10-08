<?php
declare(strict_types=1);

/**
 * Melkino V2 — uploads/backups/storage self-healing protection.
 * Extracted from legacy config.php (behavior preserved, fixed -ExecCGI self-repair bug:
 * the legacy code wrote -ExecCGI on first create, then repaired it on the next hit).
 */

if (!function_exists('melkinoProtectUploadsDir')) {
    function melkinoProtectUploadsDir(): void
    {
        $ht = "Options -Indexes\n"
            . "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phar)$\">\n"
            . "    Require all denied\n"
            . "</FilesMatch>\n"
            . "<IfModule !mod_authz_core.c>\n"
            . "    <FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phar)$\">\n"
            . "        Order allow,deny\n"
            . "        Deny from all\n"
            . "    </FilesMatch>\n"
            . "</IfModule>\n";

        foreach ([MELKINO_ROOT . '/uploads', MELKINO_ROOT . '/backups', MELKINO_STORAGE] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file = $dir . '/.htaccess';
            if (is_file($file)) {
                $cur = (string)@file_get_contents($file);
                if (strpos($cur, '-ExecCGI') !== false || strpos($cur, 'Require all denied') === false) {
                    @file_put_contents($file, $ht);
                }
                continue;
            }
            @file_put_contents($file, $ht);
        }

        // Block directory listing + direct SQL/log downloads in backups.
        $bht = MELKINO_ROOT . '/backups/.htaccess';
        $cur = is_file($bht) ? (string)@file_get_contents($bht) : '';
        if (strpos($cur, 'melkino-backups-deny') === false) {
            @file_put_contents($bht, $cur . "\n# melkino-backups-deny\n<FilesMatch \"\\.(zip|sql|log|gz)$\">\n    Require all denied\n</FilesMatch>\n");
        }
    }
}

melkinoProtectUploadsDir();
