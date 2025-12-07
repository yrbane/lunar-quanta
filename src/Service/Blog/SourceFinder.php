<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

use Lunar\Entity\Post;

class SourceFinder
{
    /**
     * Attempts to find sources for a post.
     *
     * @return array<int, array{title: string, url: string, description: string}>
     */
    public function findSources(Post $post): array
    {
        $suggestions = [];

        // 1. Extract links from content (Markdown)
        preg_match_all('/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/', $post->getContent(), $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $text = $match[1];
            $url = $match[2];
            
            // Skip internal links
            if (str_contains($url, 'lunar-quanta.com') || str_contains($url, 'localhost')) {
                continue;
            }

            $suggestions[] = [
                'title' => $text,
                'url' => $url,
                'description' => "Found in article content"
            ];
        }

        // 2. Simulate external API (Wikipedia)
        // In a real app, this would use an HTTP client to search Wikipedia/Google.
        // Here we just generate a plausible search link.
        $slug = $post->getSlug();
        $keywords = explode('-', $slug);
        $mainKeyword = $keywords[0] ?? 'tech';
        
        // Mock finding a Wikipedia source
        if (count($suggestions) < 2) {
            $suggestions[] = [
                'title' => ucfirst($mainKeyword) . " on Wikipedia",
                'url' => "https://en.wikipedia.org/wiki/" . ucfirst($mainKeyword),
                'description' => "Suggested generic reference (Automated)"
            ];
        }

        return $suggestions;
    }
}
