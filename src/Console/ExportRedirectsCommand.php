<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Console;

use Illuminate\Console\Command;
use Saazify\LaravelSeo\Models\Redirect;
use Saazify\LaravelSeo\Redirects\ServerRedirectExporter;

class ExportRedirectsCommand extends Command
{
    protected $signature = 'seo:redirects-export {path : File to write} {--format=csv : csv, apache, or nginx}';

    protected $description = 'Export redirects as CSV, Apache, or Nginx rules';

    public function handle(ServerRedirectExporter $exporter): int
    {
        $format = strtolower((string) $this->option('format'));
        $redirects = Redirect::query()->orderBy('id')->get();

        if (in_array($format, ['apache', 'nginx'], true)) {
            $body = $format === 'apache' ? $exporter->apache($redirects) : $exporter->nginx($redirects);

            if (file_put_contents((string) $this->argument('path'), $body) === false) {
                $this->error('Unable to open the export file.');

                return self::FAILURE;
            }

            $this->info('Redirects exported.');

            return self::SUCCESS;
        }

        $handle = fopen((string) $this->argument('path'), 'w');

        if ($handle === false) {
            $this->error('Unable to open the export file.');

            return self::FAILURE;
        }

        fputcsv($handle, ['source', 'destination', 'status_code', 'is_regex', 'is_active']);

        foreach ($redirects as $redirect) {
            fputcsv($handle, [
                $redirect->source,
                $redirect->destination,
                $redirect->status_code,
                $redirect->is_regex ? '1' : '0',
                $redirect->is_active ? '1' : '0',
            ]);
        }

        fclose($handle);
        $this->info('Redirects exported.');

        return self::SUCCESS;
    }
}
