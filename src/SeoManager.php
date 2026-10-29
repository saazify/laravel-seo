<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo;

use Illuminate\Support\Facades\Schema;
use Saazify\LaravelSeo\Analysis\ContentAnalyzer;
use Saazify\LaravelSeo\Analysis\MetadataSuggester;
use Saazify\LaravelSeo\Analysis\Report;
use Saazify\LaravelSeo\Data\Breadcrumb;
use Saazify\LaravelSeo\Data\FaqItem;
use Saazify\LaravelSeo\Data\HowToStep;
use Saazify\LaravelSeo\Data\NewsMeta;
use Saazify\LaravelSeo\Data\SeoData;
use Saazify\LaravelSeo\Data\VideoMeta;
use Saazify\LaravelSeo\Models\Location;
use Saazify\LaravelSeo\Rendering\HeadRenderer;

class SeoManager
{
    private SeoData $page;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private array $config)
    {
        $this->page = new SeoData;
    }

    public function reset(): self
    {
        $this->page = new SeoData;

        return $this;
    }

    public function data(): SeoData
    {
        return $this->page;
    }

    public function set(SeoData $data): self
    {
        $this->page = $data;

        return $this;
    }

    public function for(object $model): self
    {
        if (! method_exists($model, 'toSeoData')) {
            throw new \InvalidArgumentException('The model must use the HasSeo trait.');
        }

        $data = $model->toSeoData();

        if (! $data instanceof SeoData) {
            throw new \InvalidArgumentException('toSeoData() must return SeoData.');
        }

        $this->page = $data;

        return $this;
    }

    public function title(string $title): self
    {
        $this->page->title = $title;

        return $this;
    }

    public function description(string $description): self
    {
        $this->page->description = $description;

        return $this;
    }

    public function canonical(string $url): self
    {
        $this->page->canonical = $url;

        return $this;
    }

    public function url(string $url): self
    {
        $this->page->url = $url;

        return $this;
    }

    public function robots(string $robots): self
    {
        $this->page->robots = $robots;

        return $this;
    }

    public function image(string $url, ?int $width = null, ?int $height = null, ?string $alt = null): self
    {
        $this->page->image = $url;
        $this->page->imageWidth = $width;
        $this->page->imageHeight = $height;
        $this->page->imageAlt = $alt;

        return $this;
    }

    public function social(?string $title = null, ?string $description = null, ?string $image = null): self
    {
        $this->page->socialTitle = $title;
        $this->page->socialDescription = $description;
        $this->page->socialImage = $image;

        return $this;
    }

    public function type(string $type): self
    {
        $this->page->type = $type;

        return $this;
    }

    /**
     * @param  list<string>  $keyphrases
     */
    public function keyphrases(array $keyphrases): self
    {
        $this->page->keyphrases = array_values($keyphrases);

        return $this;
    }

    /**
     * @param  list<string>  $keywords
     */
    public function keywords(array $keywords): self
    {
        $this->page->keywords = array_values($keywords);

        return $this;
    }

    public function author(string $author): self
    {
        $this->page->author = $author;

        return $this;
    }

    public function publishedAt(\DateTimeInterface $date): self
    {
        $this->page->publishedAt = $date;

        return $this;
    }

    public function modifiedAt(\DateTimeInterface $date): self
    {
        $this->page->modifiedAt = $date;

        return $this;
    }

    public function cornerstone(bool $cornerstone = true): self
    {
        $this->page->cornerstone = $cornerstone;

        return $this;
    }

    /**
     * @param  list<Breadcrumb|array{name: string, url?: string|null}>  $crumbs
     */
    public function breadcrumbs(array $crumbs): self
    {
        $normalized = [];

        foreach ($crumbs as $crumb) {
            if ($crumb instanceof Breadcrumb) {
                $normalized[] = $crumb;

                continue;
            }

            $normalized[] = new Breadcrumb((string) $crumb['name'], $crumb['url'] ?? null);
        }

        if (($this->config['breadcrumbs']['prepend_home'] ?? true) === true && $normalized !== []) {
            $home = (string) ($this->config['breadcrumbs']['home_label'] ?? 'Home');

            if (mb_strtolower($normalized[0]->name) !== mb_strtolower($home)) {
                array_unshift($normalized, new Breadcrumb($home, $this->siteUrl().'/'));
            }
        }

        $this->page->breadcrumbs = $normalized;

        return $this;
    }

    /**
     * @param  list<FaqItem|array{question: string, answer: string}>  $items
     */
    public function faq(array $items): self
    {
        $this->page->faqs = array_map(function ($item) {
            if ($item instanceof FaqItem) {
                return $item;
            }

            return new FaqItem((string) $item['question'], (string) $item['answer']);
        }, $items);

        return $this;
    }

    /**
     * @param  list<HowToStep|array{name: string, text: string}>  $steps
     */
    public function howTo(array $steps): self
    {
        $this->page->howToSteps = array_map(function ($step) {
            if ($step instanceof HowToStep) {
                return $step;
            }

            return new HowToStep((string) $step['name'], (string) $step['text']);
        }, $steps);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $video
     */
    public function video(array $video): self
    {
        $meta = new VideoMeta(
            title: (string) ($video['title'] ?? ''),
            description: (string) ($video['description'] ?? ''),
            thumbnailUrl: (string) ($video['thumbnail'] ?? $video['thumbnail_url'] ?? ''),
            contentUrl: $video['content_url'] ?? $video['contentUrl'] ?? null,
            playerUrl: $video['player_url'] ?? $video['playerUrl'] ?? null,
            duration: isset($video['duration']) ? (int) $video['duration'] : null,
            publishedAt: $video['published_at'] ?? $video['publishedAt'] ?? null,
            width: isset($video['width']) ? (int) $video['width'] : null,
            height: isset($video['height']) ? (int) $video['height'] : null,
            familyFriendly: (bool) ($video['family_friendly'] ?? $video['familyFriendly'] ?? true),
            tags: array_values($video['tags'] ?? []),
            requiresSubscription: (bool) ($video['requires_subscription'] ?? false),
            live: (bool) ($video['live'] ?? false),
        );
        $this->page->videos[] = $meta;

        return $this;
    }

    public function news(NewsMeta $news): self
    {
        $this->page->news = $news;
        $this->page->type = 'article';

        return $this;
    }

    /**
     * @param  array<string, string>  $alternates
     */
    public function alternates(array $alternates): self
    {
        $this->page->alternates = $alternates;

        return $this;
    }

    public function render(): string
    {
        $this->applyCurrentUrl();
        [$organization, $branches] = $this->organization();

        return (new HeadRenderer($this->config))->render($this->page, $organization, $branches);
    }

    public function breadcrumbsHtml(): string
    {
        return (new HeadRenderer($this->config))->breadcrumbs($this->page->breadcrumbs);
    }

    public function analyze(string $html): Report
    {
        return (new ContentAnalyzer($this->config))->analyze($html, $this->page);
    }

    public function suggestTitle(string $content): string
    {
        return (new MetadataSuggester)->title($content, $this->page->primaryKeyphrase());
    }

    public function suggestDescription(string $content): string
    {
        return (new MetadataSuggester)->description($content);
    }

    public function config(): array
    {
        return $this->config;
    }

    private function applyCurrentUrl(): void
    {
        if ($this->page->canonical !== null || $this->page->url !== null) {
            return;
        }

        if (($this->config['canonical']['use_current_url'] ?? true) !== true) {
            return;
        }

        try {
            if (function_exists('request') && app()->bound('request')) {
                $this->page->canonical = request()->url();
            }
        } catch (\Throwable) {
            // Rendering outside an HTTP request keeps the canonical unset.
        }
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>}
     */
    private function organization(): array
    {
        $organization = $this->config['organization'] ?? [];
        $branches = [];

        if (($this->config['local']['enabled'] ?? false) !== true) {
            return [$organization, $branches];
        }

        try {
            if (! Schema::hasTable('seo_locations')) {
                return [$organization, $branches];
            }

            $locations = Location::query()
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->get();

            if ($locations->isEmpty()) {
                return [$organization, $branches];
            }

            $primary = $locations->first();
            $organization = $primary->toOrganizationArray($organization);

            foreach ($locations->slice(1) as $location) {
                $branches[] = $location->toOrganizationArray($organization);
            }
        } catch (\Throwable) {
            return [$this->config['organization'] ?? [], []];
        }

        return [$organization, $branches];
    }

    private function siteUrl(): string
    {
        return rtrim((string) ($this->config['site_url'] ?? ''), '/');
    }
}
