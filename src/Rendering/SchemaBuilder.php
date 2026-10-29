<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Rendering;

use Saazify\LaravelSeo\Data\Breadcrumb;
use Saazify\LaravelSeo\Data\SeoData;
use Saazify\LaravelSeo\Data\VideoMeta;
use Saazify\LaravelSeo\Local\BusinessTypes;
use Saazify\LaravelSeo\Support\Text;

final class SchemaBuilder
{
    /**
     * @param  array<string, mixed>  $organization
     * @param  list<array<string, mixed>>  $branches
     * @return array<string, mixed>
     */
    public function graph(
        SeoData $page,
        array $organization,
        array $branches,
        string $siteUrl,
        string $siteName,
        string $headline,
        ?string $searchUrl = null,
    ): array {
        $siteUrl = rtrim($siteUrl, '/');
        $canonical = $page->canonical ?: $page->url ?: $siteUrl;
        $nodes = [];

        $organization['name'] = $organization['name'] ?? $siteName;
        $nodes[] = $this->organizationNode($organization, $siteUrl, $siteUrl.'/#organization');

        foreach ($branches as $index => $branch) {
            $nodes[] = $this->organizationNode($branch, $siteUrl, $siteUrl.'/#location-'.($index + 1));
        }

        if (count($branches) > 0) {
            $items = [
                ['@id' => $siteUrl.'/#organization'],
            ];

            foreach ($branches as $index => $branch) {
                $items[] = ['@id' => $siteUrl.'/#location-'.($index + 1)];
            }

            $nodes[] = [
                '@type' => 'ItemList',
                '@id' => $siteUrl.'/#locations',
                'name' => $siteName.' locations',
                'itemListElement' => $items,
            ];
        }

        $website = [
            '@type' => 'WebSite',
            '@id' => $siteUrl.'/#website',
            'url' => $siteUrl.'/',
            'name' => $siteName,
            'publisher' => ['@id' => $siteUrl.'/#organization'],
            'inLanguage' => $page->locale,
            'potentialAction' => $this->searchAction($searchUrl),
        ];

        $nodes[] = $this->clean($website);

        $webPage = [
            '@type' => 'WebPage',
            '@id' => $canonical.'/#webpage',
            'url' => $canonical,
            'name' => $headline !== '' ? $headline : $siteName,
            'description' => $page->description,
            'isPartOf' => ['@id' => $siteUrl.'/#website'],
            'inLanguage' => $page->locale,
            'datePublished' => Text::atom($page->publishedAt),
            'dateModified' => Text::atom($page->modifiedAt),
        ];

        if ($page->breadcrumbs !== []) {
            $webPage['breadcrumb'] = ['@id' => $canonical.'/#breadcrumb'];
        }

        $isArticle = $page->type === 'article' || $page->news !== null;

        if ($isArticle) {
            $webPage['mainEntity'] = ['@id' => $canonical.'/#article'];
        }

        $nodes[] = $this->clean($webPage);

        if ($isArticle) {
            $nodes[] = $this->clean([
                '@type' => $page->news !== null ? 'NewsArticle' : 'Article',
                '@id' => $canonical.'/#article',
                'headline' => $page->news?->title ?: ($page->title ?: $headline),
                'description' => $page->description,
                'datePublished' => Text::atom($page->news?->publishedAt ?? $page->publishedAt),
                'dateModified' => Text::atom($page->modifiedAt ?? $page->publishedAt),
                'author' => $page->author ? ['@type' => 'Person', 'name' => $page->author] : null,
                'image' => $page->image,
                'articleSection' => $page->section,
                'keywords' => $this->keywords($page),
                'mainEntityOfPage' => ['@id' => $canonical.'/#webpage'],
                'publisher' => ['@id' => $siteUrl.'/#organization'],
                'inLanguage' => $page->locale,
            ]);
        }

        if ($page->breadcrumbs !== []) {
            $nodes[] = $this->breadcrumbs($page->breadcrumbs, $canonical);
        }

        if ($page->faqs !== []) {
            $nodes[] = [
                '@type' => 'FAQPage',
                '@id' => $canonical.'/#faq',
                'mainEntity' => array_map(fn ($faq) => [
                    '@type' => 'Question',
                    'name' => $faq->question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq->answer,
                    ],
                ], $page->faqs),
            ];
        }

        if ($page->howToSteps !== []) {
            $nodes[] = [
                '@type' => 'HowTo',
                '@id' => $canonical.'/#howto',
                'name' => $page->title ?: $headline,
                'step' => array_map(fn ($step, $index) => [
                    '@type' => 'HowToStep',
                    'position' => $index + 1,
                    'name' => $step->name,
                    'text' => $step->text,
                ], $page->howToSteps, array_keys($page->howToSteps)),
            ];
        }

        foreach ($page->videos as $video) {
            if ($video->isValid()) {
                $nodes[] = $this->video($video);
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $nodes,
        ];
    }

    /**
     * @param  array<string, mixed>  $org
     * @return array<string, mixed>
     */
    private function organizationNode(array $org, string $siteUrl, string $id): array
    {
        $type = BusinessTypes::normalize((string) ($org['type'] ?? 'Organization'));

        return $this->clean([
            '@type' => $type,
            '@id' => $id,
            'name' => $org['name'] ?? null,
            'url' => $org['url'] ?? $siteUrl.'/',
            'logo' => ! empty($org['logo']) ? [
                '@type' => 'ImageObject',
                'url' => $org['logo'],
                'contentUrl' => $org['logo'],
            ] : null,
            'image' => $org['image'] ?? $org['logo'] ?? null,
            'email' => $org['email'] ?? null,
            'telephone' => $org['phone'] ?? null,
            'priceRange' => $org['price_range'] ?? null,
            'vatID' => $org['vat_id'] ?? null,
            'description' => $org['description'] ?? null,
            'sameAs' => array_values(array_filter((array) ($org['same_as'] ?? []))),
            'address' => $this->address($org['address'] ?? []),
            'geo' => $this->geo($org['geo'] ?? []),
            'openingHoursSpecification' => $this->hours($org['opening_hours'] ?? []),
        ]);
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>|null
     */
    private function address(array $address): ?array
    {
        $node = $this->clean([
            '@type' => 'PostalAddress',
            'streetAddress' => $address['street'] ?? null,
            'addressLocality' => $address['city'] ?? null,
            'addressRegion' => $address['region'] ?? null,
            'postalCode' => $address['postal_code'] ?? null,
            'addressCountry' => $address['country'] ?? null,
        ]);

        return count($node) > 1 ? $node : null;
    }

    /**
     * @param  array<string, mixed>  $geo
     * @return array<string, mixed>|null
     */
    private function geo(array $geo): ?array
    {
        if (! isset($geo['latitude'], $geo['longitude'])) {
            return null;
        }

        if (! is_numeric($geo['latitude']) || ! is_numeric($geo['longitude'])) {
            return null;
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $geo['latitude'],
            'longitude' => (float) $geo['longitude'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $hours
     * @return list<array<string, mixed>>
     */
    private function hours(array $hours): array
    {
        $specs = [];

        foreach ($hours as $row) {
            if (! is_array($row) || ! empty($row['closed'])) {
                continue;
            }

            $day = $this->day((string) ($row['day'] ?? ''));

            if ($day === null || empty($row['opens']) || empty($row['closes'])) {
                continue;
            }

            $specs[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $day,
                'opens' => (string) $row['opens'],
                'closes' => (string) $row['closes'],
            ];
        }

        return $specs;
    }

    private function searchAction(?string $searchUrl): ?array
    {
        if (! is_string($searchUrl) || ! str_contains($searchUrl, '{search_term_string}')) {
            return null;
        }

        return [
            '@type' => 'SearchAction',
            'target' => $searchUrl,
            'query-input' => 'required name=search_term_string',
        ];
    }

    private function keywords(SeoData $page): ?string
    {
        $keywords = $page->news?->keywords !== [] && $page->news !== null
            ? $page->news->keywords
            : $page->keywords;

        return $keywords !== [] ? implode(', ', $keywords) : null;
    }

    private function day(string $day): ?string
    {
        $day = trim($day);

        if ($day === '') {
            return null;
        }

        if (str_starts_with($day, 'https://schema.org/')) {
            return $day;
        }

        $normalized = ucfirst(strtolower($day));
        $allowed = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return in_array($normalized, $allowed, true) ? 'https://schema.org/'.$normalized : null;
    }

    /**
     * @param  list<Breadcrumb>  $crumbs
     * @return array<string, mixed>
     */
    private function breadcrumbs(array $crumbs, string $canonical): array
    {
        $elements = [];

        foreach (array_values($crumbs) as $index => $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb->name,
            ];

            if ($crumb->url) {
                $item['item'] = $crumb->url;
            }

            $elements[] = $item;
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $canonical.'/#breadcrumb',
            'itemListElement' => $elements,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function video(VideoMeta $video): array
    {
        return $this->clean([
            '@type' => 'VideoObject',
            'name' => $video->title,
            'description' => $video->description,
            'thumbnailUrl' => $video->thumbnailUrl,
            'uploadDate' => Text::atom($video->publishedAt),
            'duration' => $video->duration !== null ? Text::duration($video->duration) : null,
            'contentUrl' => $video->contentUrl,
            'embedUrl' => $video->playerUrl,
            'width' => $video->width,
            'height' => $video->height,
            'isFamilyFriendly' => $video->familyFriendly,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function clean(array $data): array
    {
        $isList = array_is_list($data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->clean($value);

                if ($data[$key] === []) {
                    unset($data[$key]);
                }

                continue;
            }

            if ($value === null || $value === '') {
                unset($data[$key]);
            }
        }

        if ($isList) {
            $data = array_values($data);
        }

        return $data;
    }
}
