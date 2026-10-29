<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Data;

use Saazify\LaravelSeo\Models\SeoRecord;

final class SeoData
{
    /**
     * @param  list<string>  $keywords
     * @param  list<string>  $keyphrases
     * @param  list<Breadcrumb>  $breadcrumbs
     * @param  list<FaqItem>  $faqs
     * @param  list<HowToStep>  $howToSteps
     * @param  list<VideoMeta>  $videos
     * @param  list<string>  $images
     * @param  array<string, string>  $alternates
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $canonical = null,
        public ?string $url = null,
        public string $robots = 'index, follow',
        public ?string $image = null,
        public ?int $imageWidth = null,
        public ?int $imageHeight = null,
        public ?string $imageAlt = null,
        public ?string $socialTitle = null,
        public ?string $socialDescription = null,
        public ?string $socialImage = null,
        public string $type = 'website',
        public ?string $locale = null,
        public array $keywords = [],
        public array $keyphrases = [],
        public ?string $author = null,
        public ?string $section = null,
        public ?\DateTimeInterface $publishedAt = null,
        public ?\DateTimeInterface $modifiedAt = null,
        public array $breadcrumbs = [],
        public array $faqs = [],
        public array $howToSteps = [],
        public array $videos = [],
        public ?NewsMeta $news = null,
        public bool $cornerstone = false,
        public array $images = [],
        public ?float $sitemapPriority = null,
        public ?string $sitemapChangefreq = null,
        public bool $includeInSitemap = true,
        public ?string $titleTemplate = null,
        public ?int $pageNumber = null,
        public array $alternates = [],
        public ?string $prevUrl = null,
        public ?string $nextUrl = null,
    ) {}

    public function applyRecord(SeoRecord $record): self
    {
        $map = [
            'title' => 'title',
            'description' => 'description',
            'canonical' => 'canonical',
            'robots' => 'robots',
            'image' => 'image',
            'image_alt' => 'imageAlt',
            'social_title' => 'socialTitle',
            'social_description' => 'socialDescription',
            'social_image' => 'socialImage',
            'locale' => 'locale',
        ];

        foreach ($map as $column => $property) {
            $value = $record->{$column};

            if ($value !== null && $value !== '') {
                $this->{$property} = $value;
            }
        }

        if (is_array($record->keyphrases) && $record->keyphrases !== []) {
            $this->keyphrases = array_values($record->keyphrases);
        }

        if (is_array($record->keywords) && $record->keywords !== []) {
            $this->keywords = array_values($record->keywords);
        }

        $this->cornerstone = (bool) $record->cornerstone;
        $this->includeInSitemap = (bool) $record->include_in_sitemap;

        if ($record->sitemap_priority !== null) {
            $this->sitemapPriority = (float) $record->sitemap_priority;
        }

        if ($record->sitemap_changefreq) {
            $this->sitemapChangefreq = (string) $record->sitemap_changefreq;
        }

        if ($this->url === null && $this->canonical !== null) {
            $this->url = $this->canonical;
        }

        return $this;
    }

    public function primaryKeyphrase(): ?string
    {
        $phrase = $this->keyphrases[0] ?? null;

        return is_string($phrase) && trim($phrase) !== '' ? trim($phrase) : null;
    }
}
