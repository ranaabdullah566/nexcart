<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LicenseKeys extends Command
{
    protected $signature = 'nexcart:license:keys {--out= : Directory to write license-private.key and license-public.key into}';

    protected $description = 'VENDOR: create the Ed25519 key pair used to sign license keys';

    public function handle(): int
    {
        $pair = sodium_crypto_sign_keypair();
        $private = base64_encode(sodium_crypto_sign_secretkey($pair));
        $public = base64_encode(sodium_crypto_sign_publickey($pair));

        if ($dir = $this->option('out')) {
            @mkdir($dir, 0700, true);
            file_put_contents($dir.'/license-private.key', $private);
            file_put_contents($dir.'/license-public.key', $public);
            @chmod($dir.'/license-private.key', 0600);
            $this->info("Written to {$dir}");
        }

        $this->line('PUBLIC key (ship it: config/license.php or NEXCART_LICENSE_PUBLIC_KEY):');
        $this->line($public);
        $this->newLine();
        $this->warn('PRIVATE key (keep it secret, never deploy it, back it up — you cannot issue licenses without it):');
        $this->line($private);

        return self::SUCCESS;
    }
}
