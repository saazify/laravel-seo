<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Console;

use Illuminate\Console\Command;
use Saazify\LaravelSeo\Exceptions\InvalidRedirectException;
use Saazify\LaravelSeo\Models\Redirect;

class ImportRedirectsCommand extends Command
{
    protected $signature = 'seo:redirects-import {path : CSV file to read}';

    protected $description = 'Import redirects from a CSV file';

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $handle = fopen($path, 'r');

        if ($handle === false) {
            $this->error('Unable to open the import file.');

            return self::FAILURE;
        }

        $imported = 0;
        $skipped = 0;
        $first = true;

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            $source = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? '')) ?? '';

            if ($first && strtolower(trim($source)) === 'source') {
                $first = false;

                continue;
            }

            $first = false;

            try {
                $normalized = Redirect::normalize([
                    'source' => $source,
                    'destination' => $row[1] ?? null,
                    'status_code' => $row[2] ?? 301,
                    'is_regex' => $row[3] ?? false,
                    'is_active' => $row[4] ?? true,
                ]);

                Redirect::query()->updateOrCreate(
                    ['source' => $normalized['source']],
                    $normalized,
                );
                $imported++;
            } catch (InvalidRedirectException) {
                $skipped++;
            }
        }

        fclose($handle);
        $this->info("Imported {$imported} redirect(s). Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
