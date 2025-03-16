<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class TokenOptimizer
{
    private const MAX_TOKENS = 4000;
    private const TOKENS_PER_WORD = 1.3; // Average for English
    private const RESERVED_TOKENS = 500; // For system prompts and response

    public function optimizeContent(string $subject, string $body): string
    {
        $availableTokens = self::MAX_TOKENS - self::RESERVED_TOKENS;
        
        // Prioritize the subject
        $subjectTokens = $this->estimateTokens($subject);
        $availableTokens -= $subjectTokens;

        // Clean and truncate body if needed
        $cleanBody = $this->cleanEmailBody($body);
        $bodyTokens = $this->estimateTokens($cleanBody);

        if ($bodyTokens > $availableTokens) {
            $cleanBody = $this->truncateToTokenLimit($cleanBody, $availableTokens);
        }

        return $subject . "\n\n" . $cleanBody;
    }

    private function cleanEmailBody(string $body): string
    {
        // Remove email signatures
        $body = preg_replace('/^--\s*$/m', '', $body);
        
        // Remove quotes from previous emails
        $body = preg_replace('/^>.*$/m', '', $body);
        
        // Remove common email footers
        $footers = [
            '/Sent from my iPhone/i',
            '/Sent from my iPad/i',
            '/Get Outlook for iOS/i',
            '/Best regards,.*/is',
            '/Kind regards,.*/is',
            '/Thanks,.*/is',
            '/Cheers,.*/is'
        ];
        
        $body = preg_replace($footers, '', $body);
        
        // Clean up multiple newlines
        $body = preg_replace('/\n{3,}/', "\n\n", $body);
        
        return trim($body);
    }

    private function estimateTokens(string $text): int
    {
        return (int)(str_word_count($text) * self::TOKENS_PER_WORD);
    }

    private function truncateToTokenLimit(string $text, int $tokenLimit): string
    {
        $words = str_word_count($text, 1);
        $wordLimit = (int)($tokenLimit / self::TOKENS_PER_WORD);
        
        if (count($words) <= $wordLimit) {
            return $text;
        }

        $truncatedWords = array_slice($words, 0, $wordLimit);
        return implode(' ', $truncatedWords) . '...';
    }
}