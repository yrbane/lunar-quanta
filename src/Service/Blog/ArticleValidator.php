<?php

declare(strict_types=1);

namespace Lunar\Service\Blog;

use Lunar\Entity\Post;

class ArticleValidator
{
    /**
     * @return string[] List of validation errors
     */
    public function validate(Post $post): array
    {
        $errors = [];

        if (strlen($post->getTitle()) < 5) {
            $errors[] = "Title is too short (min 5 chars).";
        }

        if ($post->getWordCount() < 100) {
            $errors[] = "Content is too short (min 100 words).";
        }

        if (empty($post->getAuthor())) {
            $errors[] = "Author is missing.";
        }

        if (!$post->hasValidSources(2)) {
            $errors[] = "Insufficient sources (min 2 unique domains required).";
        }

        return $errors;
    }
}
