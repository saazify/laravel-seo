<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Analysis;

use Saazify\LaravelSeo\Support\Text;

final class MetadataSuggester
{
    public function title(string $content, ?string $keyphrase = null): string
    {
        $plain = Text::plain($content);
        $sentence = Text::sentences($plain)[0] ?? $plain;
        $sentence = Text::limit($sentence, 60);

        if ($keyphrase !== null && trim($keyphrase) !== '' && ! Text::containsPhrase($sentence, $keyphrase)) {
            $sentence = Text::limit(trim($keyphrase).': '.$sentence, 60);
        }

        return $sentence;
    }

    public function description(string $content): string
    {
        return Text::limit(Text::plain($content), 155);
    }
}
