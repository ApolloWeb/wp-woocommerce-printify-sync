<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

class AIWebhookHandler {
    private $logger;
    private $settings;
    private $event_handlers = [
        'model.trained' => 'handleModelTrained',
        'model.error' => 'handleModelError',
        'prediction.made' => 'handlePrediction'
    ];

    public function __construct(Settings $settings, Logger $logger) {
        $this->settings = $settings;
        $this->logger = $logger;
    }

    public function handle(): void {
        $payload = $this->validatePayload();
        if (!$payload) return;

        $event = $payload['event'] ?? '';
        if (isset($this->event_handlers[$event])) {
            $this->{$this->event_handlers[$event]}($payload);
        }
    }

    private function handleModelTrained(array $data): void {
        $metrics = $data['metrics'] ?? [];
        update_option('wpps_ai_model_metrics', $metrics);
        do_action('wpps_ai_model_trained', $metrics);
    }

    private function validatePayload(): ?array {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_AI_SIGNATURE'] ?? '';

        if (!$this->verifySignature($payload, $signature)) {
            $this->logger->log('Invalid AI webhook signature', 'error');
            status_header(401);
            return null;
        }

        return json_decode($payload, true);
    }
}
