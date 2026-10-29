<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Console;

use Illuminate\Console\Command;
use Saazify\LaravelSeo\Models\SeoRecord;

class ExportKeyphrasesCommand extends Command
{
    protected $signature = 'seo:keyphrases-export {path : CSV file to write}';

    protected $description = 'Export focus keyphrases to a CSV file';

    public function handle(): int
    {
        $handle = fopen((string) $this->argument('path'), 'w');

        if ($handle === false) {
            $this->error('Unable to open the export file.');

            return self::FAILURE;
        }

        fputcsv($handle, ['title', 'canonical', 'keyphrases', 'cornerstone']);

        foreach (SeoRecord::query()->orderBy('id')->lazy() as $record) {
            fputcsv($handle, [
                $record->title,
                $record->canonical,
                implode('|', $record->keyphrases ?? []),
                $record->cornerstone ? '1' : '0',
            ]);
        }

        fclose($handle);
        $this->info('Keyphrases exported.');

        return self::SUCCESS;
    }
}
