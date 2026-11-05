<?php

namespace App\Console\Commands;

use App\Support\License;
use Illuminate\Console\Command;

class LicenseGenerate extends Command
{
    protected $signature = 'nexcart:license:generate {licensee : Business name} {--email=} {--domain=* : Domain it may run on: example.com, *.example.com or * for any} {--edition=standard} {--expires= : YYYY-MM-DD, empty = lifetime} {--private-key= : Path to the private key file (or set NEXCART_LICENSE_PRIVATE_KEY)}';

    protected $description = 'VENDOR: issue a signed license key for a customer';

    public function handle(): int
    {
        $private = $this->option('private-key') ? trim((string) @file_get_contents($this->option('private-key'))) : (string) env('NEXCART_LICENSE_PRIVATE_KEY');
        if ($private === '') {
            $this->error('Give --private-key=path or set NEXCART_LICENSE_PRIVATE_KEY.');

            return self::FAILURE;
        }
        if ($this->option('expires') && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->option('expires'))) {
            $this->error('--expires must look like 2027-12-31.');

            return self::FAILURE;
        }

        $domains = array_filter((array) $this->option('domain')) ?: ['*'];
        foreach ($domains as $domain) {
            $key = License::issue([
                'id' => 'L-'.strtoupper(bin2hex(random_bytes(4))), 'licensee' => $this->argument('licensee'), 'email' => $this->option('email'),
                'domain' => $domain, 'edition' => $this->option('edition'), 'issued' => now()->toDateString(), 'expires' => $this->option('expires') ?: null,
            ], $private);
            $this->line("<info>{$domain}</info>");
            $this->line($key);
        }

        return self::SUCCESS;
    }
}
