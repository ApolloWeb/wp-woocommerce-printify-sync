<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AIResponseGenerator {
    private $chatgpt;
    private $template_loader;
    private $logger;

    public function generateResponse($context) {
        $analysis = $this->chatgpt->analyze([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getResponsePrompt()
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($context)
                ]
            ]
        ]);

        return [
            'template' => $analysis['response_template'],
            'tone' => $analysis['tone'],
            'suggested_actions' => $analysis['suggested_actions'],
            'next_steps' => $analysis['next_steps']
        ];
    }

    private function getResponsePrompt() {
        return <<<EOT
Generate a customer service response considering:
1. Issue type and severity
2. Customer history and tone
3. Required actions and next steps
4. Business policy compliance
Format as professional, empathetic communication.
Include specific details about the issue.
EOT;
    }
}
