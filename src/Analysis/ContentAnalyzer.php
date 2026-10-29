<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Analysis;

use Saazify\LaravelSeo\Data\SeoData;
use Saazify\LaravelSeo\Support\Text;

final class ContentAnalyzer
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private array $config = []) {}

    public function analyze(string $html, SeoData $seo): Report
    {
        $plain = Text::plain($html);
        $words = Text::words($plain);
        $wordCount = count($words);
        $wpm = max(1, (int) ($this->config['analysis']['reading_wpm'] ?? 200));
        $checks = [];

        $checks[] = $this->titleLength($seo->title);
        $checks[] = $this->descriptionLength($seo->description);

        $phrase = $seo->primaryKeyphrase();
        $maxPhrases = (int) ($this->config['analysis']['max_keyphrases'] ?? 5);

        if (count($seo->keyphrases) > $maxPhrases) {
            $checks[] = new Check('keyphrase-count', 'Keyphrases', 'poor', 'Use at most '.$maxPhrases.' keyphrases. The first one is the focus keyphrase.');
        }

        if ($phrase === null) {
            $checks[] = new Check('keyphrase', 'Focus keyphrase', 'ok', 'No focus keyphrase is set.');
        } else {
            $checks[] = $this->presence('keyphrase-title', 'Keyphrase in title', $seo->title ?? '', $phrase, 'Use the focus keyphrase in the SEO title.');
            $checks[] = $this->presence('keyphrase-description', 'Keyphrase in description', $seo->description ?? '', $phrase, 'Use the focus keyphrase in the meta description.');
            $checks[] = $this->presence('keyphrase-url', 'Keyphrase in URL', str_replace(['-', '_'], ' ', (string) $seo->url), $phrase, 'Use the focus keyphrase in the URL.');
            $introduction = implode(' ', array_slice($words, 0, 100));
            $checks[] = $this->presence('keyphrase-intro', 'Keyphrase in introduction', $introduction, $phrase, 'Use the focus keyphrase in the first 100 words.');
            $checks[] = $this->density($plain, $phrase, $wordCount);

            $related = array_slice($seo->keyphrases, 1, max(0, $maxPhrases - 1));

            if ($related !== []) {
                $found = 0;

                foreach ($related as $item) {
                    if (is_string($item) && Text::containsPhrase($plain, $item)) {
                        $found++;
                    }
                }

                $checks[] = match (true) {
                    $found === count($related) => new Check('related-keyphrases', 'Related keyphrases', 'good', 'Every related keyphrase appears in the content.'),
                    $found > 0 => new Check('related-keyphrases', 'Related keyphrases', 'ok', $found.' of '.count($related).' related keyphrases appear in the content.'),
                    default => new Check('related-keyphrases', 'Related keyphrases', 'poor', 'None of the related keyphrases appear in the content.'),
                };
            }
        }

        $checks[] = $this->lengthBand(
            'content-length',
            'Content length',
            $wordCount,
            300,
            150,
            $wordCount.' words. Aim for at least 300 words.'
        );

        if ($seo->cornerstone) {
            $minimum = (int) ($this->config['analysis']['cornerstone_words'] ?? 900);
            $checks[] = $this->lengthBand(
                'cornerstone-length',
                'Cornerstone content',
                $wordCount,
                $minimum,
                (int) ($minimum * 0.6),
                'Cornerstone pages should be at least '.$minimum.' words.'
            );
        }

        array_push($checks, ...$this->readability($html, $plain, $wordCount));
        array_push($checks, ...$this->inclusive($plain));

        $points = array_sum(array_map(fn (Check $check) => $check->points(), $checks));
        $max = max(1, count($checks) * 2);
        $score = (int) round(($points / $max) * 100);
        $status = $score >= 80 ? 'good' : ($score >= 50 ? 'ok' : 'poor');

        return new Report($score, $status, $wordCount, (int) max(1, ceil($wordCount / $wpm)), $checks);
    }

    private function titleLength(?string $title): Check
    {
        $length = mb_strlen(trim((string) $title));

        return match (true) {
            $length >= 50 && $length <= 60 => new Check('title-length', 'SEO title length', 'good', 'The title is '.$length.' characters.'),
            $length >= 30 && $length <= 70 => new Check('title-length', 'SEO title length', 'ok', 'The title is '.$length.' characters. 50 to 60 is a comfortable length.'),
            $length === 0 => new Check('title-length', 'SEO title length', 'poor', 'Add an SEO title.'),
            default => new Check('title-length', 'SEO title length', 'poor', 'The title is '.$length.' characters. Keep it between 50 and 60.'),
        };
    }

    private function descriptionLength(?string $description): Check
    {
        $length = mb_strlen(trim((string) $description));

        return match (true) {
            $length >= 120 && $length <= 160 => new Check('description-length', 'Meta description', 'good', 'The description is '.$length.' characters.'),
            $length >= 80 && $length <= 170 => new Check('description-length', 'Meta description', 'ok', 'The description is '.$length.' characters. 120 to 160 is a comfortable length.'),
            $length === 0 => new Check('description-length', 'Meta description', 'poor', 'Add a meta description.'),
            default => new Check('description-length', 'Meta description', 'poor', 'The description is '.$length.' characters. Keep it between 120 and 160.'),
        };
    }

    private function presence(string $id, string $title, string $haystack, string $phrase, string $failure): Check
    {
        if (Text::containsPhrase($haystack, $phrase)) {
            return new Check($id, $title, 'good', 'The focus keyphrase appears here.');
        }

        return new Check($id, $title, 'poor', $failure);
    }

    private function density(string $plain, string $phrase, int $wordCount): Check
    {
        if ($wordCount === 0) {
            return new Check('keyphrase-density', 'Keyphrase density', 'poor', 'There is no content to measure.');
        }

        $pattern = '/'.preg_quote($phrase, '/').'/iu';
        $count = preg_match_all($pattern, $plain) ?: 0;
        $phraseWords = max(1, count(Text::words($phrase)));
        $density = ($count * $phraseWords / $wordCount) * 100;

        return match (true) {
            $density >= 0.5 && $density <= 2.5 => new Check('keyphrase-density', 'Keyphrase density', 'good', 'Density is '.number_format($density, 2).'%.'),
            $density >= 0.1 && $density <= 3.5 => new Check('keyphrase-density', 'Keyphrase density', 'ok', 'Density is '.number_format($density, 2).'%. Aim for 0.5% to 2.5%.'),
            default => new Check('keyphrase-density', 'Keyphrase density', 'poor', 'Density is '.number_format($density, 2).'%. Aim for 0.5% to 2.5%.'),
        };
    }

    private function lengthBand(string $id, string $title, int $words, int $good, int $ok, string $message): Check
    {
        if ($words >= $good) {
            return new Check($id, $title, 'good', $words.' words.');
        }

        if ($words >= $ok) {
            return new Check($id, $title, 'ok', $message);
        }

        return new Check($id, $title, 'poor', $message);
    }

    /**
     * @return list<Check>
     */
    private function readability(string $html, string $plain, int $wordCount): array
    {
        $checks = [];
        $sentences = Text::sentences($plain);

        if ($sentences === []) {
            $checks[] = new Check('sentence-length', 'Sentence length', 'poor', 'Write in complete sentences.');
        } else {
            $lengths = array_map(fn (string $sentence) => count(Text::words($sentence)), $sentences);
            $average = array_sum($lengths) / count($lengths);
            $checks[] = match (true) {
                $average <= 20 => new Check('sentence-length', 'Sentence length', 'good', 'Average sentence length is '.round($average, 1).' words.'),
                $average <= 25 => new Check('sentence-length', 'Sentence length', 'ok', 'Average sentence length is '.round($average, 1).' words. Try to stay under 20.'),
                default => new Check('sentence-length', 'Sentence length', 'poor', 'Average sentence length is '.round($average, 1).' words. Shorten long sentences.'),
            };
        }

        $paragraphs = preg_split('/<\/p>|\n{2,}/i', $html) ?: [];
        $long = 0;

        foreach ($paragraphs as $paragraph) {
            if (count(Text::words(Text::plain($paragraph))) > 150) {
                $long++;
            }
        }

        $checks[] = $long === 0
            ? new Check('paragraph-length', 'Paragraph length', 'good', 'No paragraph is longer than 150 words.')
            : new Check('paragraph-length', 'Paragraph length', 'poor', $long.' paragraph(s) are longer than 150 words.');

        $subheadings = preg_match_all('/<h[2-3]\b/i', $html) ?: 0;

        if ($wordCount > 300) {
            $checks[] = $subheadings > 0 && ($wordCount / $subheadings) <= 300
                ? new Check('subheadings', 'Subheadings', 'good', 'Subheadings break the text into scannable sections.')
                : new Check('subheadings', 'Subheadings', 'poor', 'Add a subheading about every 300 words.');
        }

        if ($sentences !== []) {
            $passive = preg_match_all('/\b(am|is|are|was|were|be|been|being)\s+\w+ed\b/iu', $plain) ?: 0;
            $ratio = $passive / count($sentences);
            $checks[] = match (true) {
                $ratio <= 0.1 => new Check('passive-voice', 'Passive voice', 'good', 'Passive voice is uncommon.'),
                $ratio <= 0.2 => new Check('passive-voice', 'Passive voice', 'ok', 'Some sentences look passive. Prefer an active verb where you can.'),
                default => new Check('passive-voice', 'Passive voice', 'poor', 'Many sentences look passive. Prefer an active verb.'),
            };
        }

        if ($wordCount >= 200 && $sentences !== []) {
            $transitions = ['however', 'therefore', 'for example', 'for instance', 'in addition', 'also', 'finally', 'meanwhile', 'because', 'although', 'instead', 'then', 'specifically'];
            $hits = 0;

            foreach ($sentences as $sentence) {
                foreach ($transitions as $transition) {
                    if (preg_match('/\b'.preg_quote($transition, '/').'\b/iu', $sentence) === 1) {
                        $hits++;
                        break;
                    }
                }
            }

            $ratio = $hits / count($sentences);
            $checks[] = match (true) {
                $ratio >= 0.3 => new Check('transition-words', 'Transition words', 'good', 'Sentences connect with transition words.'),
                $ratio >= 0.2 => new Check('transition-words', 'Transition words', 'ok', 'A few more transition words would make the text easier to follow.'),
                default => new Check('transition-words', 'Transition words', 'poor', 'Use transition words such as "however" or "for example" to connect sentences.'),
            };
        }

        return $checks;
    }

    /**
     * @return list<Check>
     */
    private function inclusive(string $plain): array
    {
        $map = $this->config['analysis']['inclusive'] ?? [];

        if (! is_array($map) || $map === []) {
            return [];
        }

        $found = [];

        foreach ($map as $phrase => $alternative) {
            if (! is_string($phrase) || ! is_string($alternative)) {
                continue;
            }

            if (preg_match('/\b'.preg_quote($phrase, '/').'\b/iu', $plain) === 1) {
                $found[] = '"'.$phrase.'" → "'.$alternative.'"';
            }
        }

        if ($found === []) {
            return [new Check('inclusive-language', 'Inclusive language', 'good', 'None of the configured phrases were found.')];
        }

        return [new Check('inclusive-language', 'Inclusive language', 'poor', 'Consider: '.implode('; ', $found))];
    }
}
