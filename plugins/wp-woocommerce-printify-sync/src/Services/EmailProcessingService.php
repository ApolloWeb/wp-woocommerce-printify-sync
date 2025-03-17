<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class EmailProcessingService
{
    private $openai;
    private $fallback;
    private $logger;
    
    public function __construct(
        OpenAIHandler $openai,
        FallbackEmailProcessor $fallback,
        LoggerInterface $logger
    ) {
        $this->openai = $openai;
        $this->fallback = $fallback;
        $this->logger = $logger;
    }

    public function processEmail(array $email): int
    {
        try {
            // Clean and prepare email content
            $content = $this->cleanEmailContent($email['body']);
            
            // Decide processing method
            $analysis = $this->shouldUseAI() 
                ? $this->analyzeWithChatGPT($content, $email['subject'])
                : $this->fallback->analyzeEmail($email['subject'], $content);
            
            // Create or update ticket
            return $this->createOrUpdateTicket($email, $analysis);

        } catch (\Exception $e) {
            $this->logger->error('Email processing failed', [
                'error' => $e->getMessage(),
                'email_id' => $email['message_id'] ?? null
            ]);

            // If AI fails, try fallback
            if ($this->shouldUseAI()) {
                try {
                    $analysis = $this->fallback->analyzeEmail($email['subject'], $content);
                    return $this->createOrUpdateTicket($email, $analysis);
                } catch (\Exception $e2) {
                    $this->logger->error('Fallback processing failed', [
                        'error' => $e2->getMessage()
                    ]);
                    throw $e2;
                }
            }
            
            throw $e;
        }
    }

    private function shouldUseAI(): bool
    {
        return get_option('wpwps_enable_ai', false) && 
               !empty(get_option('wpwps_openai_api_key', ''));
    }

    private function analyzeWithChatGPT(string $content, string $subject): array
    {
        // Limit content length for API
        $maxLength = 4000; // Reserve space for prompt
        if (mb_strlen($content) > $maxLength) {
            $content = mb_substr($content, 0, $maxLength - 3) . '...';
        }

        $prompt = $this->buildAnalysisPrompt($subject, $content);
        
        $response = $this->openai->createCompletion([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a customer service email analyzer. Extract key information and categorize the email.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 500
        ]);

        return json_decode($response['choices'][0]['message']['content'], true);
    }
}