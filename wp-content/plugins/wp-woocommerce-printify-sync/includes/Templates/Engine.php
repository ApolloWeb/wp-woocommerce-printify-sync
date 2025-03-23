<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Templates;

class Engine {
    private $template_path;
    private $data = [];
    private $sections = [];
    private $cache = [];
    private $section_stack = [];
    private $cache_expiration = 3600; // 1 hour
    private $composers = [];
    private $stacks = [];
    private $flash = [];
    private $compiled_path;
    private $shared = [];
    private $debug = false;
    private $namespaces = [];
    private $filters = [];
    private $macros = [];
    private $directives = [];
    private $parent = null;
    private $slots = [];
    private $components = [];
    private $fragments = [];
    private $middleware = [];
    private $overrides = [];
    private $errors = [];
    private $sync_status = [];

    public function __construct(string $template_path) {
        $this->template_path = $template_path;
        $this->compiled_path = WP_CONTENT_DIR . '/cache/wpps/templates';
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;
        
        $this->data = [
            'assets_url' => WPPS_PUBLIC_URL,
            'admin_url' => admin_url(),
            'debug' => $this->debug
        ];
        $this->flash = get_transient('wpps_flash_messages') ?: [];
        delete_transient('wpps_flash_messages');
        $this->registerDefaultDirectives();
        $this->registerDefaultFilters();
        $this->registerDefaultComponents();
        $this->registerDefaultSyncComponents();
        $this->registerSyncComponents();
        $this->registerShippingComponents();
        $this->registerTicketComponents();
    }

    private function registerDefaultComponents(): void {
        $this->component('alert', function($type = 'info', $message = '') {
            return "<div class='alert alert-{$type}'>{$message}</div>";
        });
    }

    private function registerDefaultSyncComponents(): void {
        $this->component('sync_status', function($type, $status, $message = '') {
            $class = $status === 'success' ? 'success' : 'error';
            return "<div class='sync-status sync-{$class}'>{$message}</div>";
        });

        $this->component('sync_progress', function($total, $current) {
            $percent = ($current / $total) * 100;
            return "<div class='sync-progress'>
                <div class='progress-bar' style='width: {$percent}%'></div>
            </div>";
        });
    }

    private function registerSyncComponents(): void {
        $this->component('sync_badge', function($status) {
            $classes = [
                'pending' => 'bg-warning',
                'processing' => 'bg-info',
                'completed' => 'bg-success',
                'failed' => 'bg-danger'
            ];
            $class = $classes[$status] ?? 'bg-secondary';
            return "<span class='badge {$class}'>{$status}</span>";
        });

        $this->component('sync_progress_bar', function($current, $total) {
            $percent = min(($current / max(1, $total)) * 100, 100);
            return "<div class='progress'>
                <div class='progress-bar' role='progressbar' 
                     style='width: {$percent}%' 
                     aria-valuenow='{$current}' 
                     aria-valuemin='0' 
                     aria-valuemax='{$total}'>
                    {$current}/{$total}
                </div>
            </div>";
        });

        $this->component('sync_error_list', function($errors) {
            if (empty($errors)) return '';
            
            $html = '<div class="sync-errors mt-3">';
            foreach ($errors as $error) {
                $html .= "<div class='alert alert-danger'>
                    <strong>{$error['source']}</strong>: {$error['message']}
                </div>";
            }
            return $html . '</div>';
        });
    }

    private function registerShippingComponents(): void {
        $this->component('shipping_zone_card', function($zone) {
            return "<div class='card shipping-zone mb-3'>
                <div class='card-header d-flex justify-content-between'>
                    <h5 class='mb-0'>{$zone['name']}</h5>
                    <span class='badge bg-info'>{$zone['region']}</span>
                </div>
                <div class='card-body'>
                    {$this->renderComponent('shipping_methods_list', $zone['methods'])}
                </div>
            </div>";
        });

        $this->component('shipping_methods_list', function($methods) {
            if (empty($methods)) return '<p>No shipping methods configured</p>';
            
            $html = '<div class="shipping-methods">';
            foreach ($methods as $method) {
                $html .= "<div class='shipping-method d-flex justify-content-between'>
                    <span>{$method['title']}</span>
                    <span>{$method['cost']}</span>
                </div>";
            }
            return $html . '</div>';
        });

        $this->component('profile_mapper', function($profiles, $selected = '') {
            $html = '<select class="form-select profile-mapper">';
            foreach ($profiles as $profile) {
                $selected_attr = $profile['id'] === $selected ? 'selected' : '';
                $html .= "<option value='{$profile['id']}' {$selected_attr}>
                    {$profile['name']}
                </option>";
            }
            return $html . '</select>';
        });
    }

    private function registerTicketComponents(): void {
        $this->component('ticket_list', function($tickets) {
            $html = '<div class="ticket-list">';
            foreach ($tickets as $ticket) {
                $html .= $this->renderComponent('ticket_card', $ticket);
            }
            return $html . '</div>';
        });

        $this->component('ticket_card', function($ticket) {
            return "<div class='card ticket-card mb-3'>
                <div class='card-header d-flex justify-content-between align-items-center'>
                    <h6 class='mb-0'>#{$ticket['id']} - {$ticket['subject']}</h6>
                    {$this->renderComponent('ticket_status_badge', $ticket['status'])}
                </div>
                <div class='card-body'>
                    <p class='ticket-preview'>{$this->truncate($ticket['body'], 100)}</p>
                    <div class='ticket-meta'>
                        <small class='text-muted'>
                            {$this->formatDate($ticket['created_at'])}
                        </small>
                        <span class='badge bg-info'>{$ticket['category']}</span>
                    </div>
                </div>
            </div>";
        });

        $this->component('ticket_status_badge', function($status) {
            $classes = [
                'new' => 'bg-primary',
                'open' => 'bg-warning',
                'pending' => 'bg-info',
                'resolved' => 'bg-success',
                'closed' => 'bg-secondary'
            ];
            $class = $classes[$status] ?? 'bg-secondary';
            return "<span class='badge {$class}'>{$status}</span>";
        });

        $this->component('ticket_response_form', function($ticket_id) {
            return "<form class='ticket-response-form mt-3' method='post'>
                <input type='hidden' name='ticket_id' value='{$ticket_id}'>
                <div class='form-group'>
                    <textarea name='response' class='form-control' rows='3' 
                              placeholder='Enter your response...'></textarea>
                </div>
                <div class='form-group mt-2'>
                    <button type='submit' class='btn btn-primary'>Send Response</button>
                </div>
            </form>";
        });

        $this->component('ai_chat_interface', function($ticket_id) {
            return "<div class='ai-chat-interface'>
                <div class='chat-messages' data-ticket-id='{$ticket_id}'></div>
                <div class='chat-input-area'>
                    <textarea class='form-control chat-input' placeholder='Type your message...'></textarea>
                    <div class='d-flex justify-content-between align-items-center mt-2'>
                        <div class='ai-suggestions'></div>
                        <button class='btn btn-primary send-message'>Send</button>
                    </div>
                </div>
            </div>";
        });

        $this->component('conversation_history', function($messages) {
            $html = '<div class="conversation-history">';
            foreach ($messages as $message) {
                $sentiment_class = $this->getSentimentClass($message['sentiment']);
                $html .= "<div class='message {$message['type']} {$sentiment_class}'>
                    <div class='message-header'>
                        <span class='sender'>{$message['sender']}</span>
                        <span class='time'>{$this->formatDate($message['time'])}</span>
                    </div>
                    <div class='message-content'>{$message['content']}</div>
                    {$this->renderComponent('message_actions', $message)}
                </div>";
            }
            return $html . '</div>';
        });

        $this->component('message_actions', function($message) {
            if ($message['type'] !== 'ai_suggestion') return '';
            
            return "<div class='message-actions mt-2'>
                <button class='btn btn-sm btn-outline-primary accept-suggestion'>
                    <i class='fas fa-check'></i> Use This Response
                </button>
                <button class='btn btn-sm btn-outline-secondary modify-suggestion'>
                    <i class='fas fa-edit'></i> Modify
                </button>
            </div>";
        });

        $this->component('ai_analytics', function($ticket_id) {
            return "<div class='ai-analytics card mt-3'>
                <div class='card-header'>
                    <h6 class='mb-0'>Conversation Analytics</h6>
                </div>
                <div class='card-body'>
                    <div class='sentiment-trend' data-ticket='{$ticket_id}'></div>
                    <div class='key-topics mt-3'></div>
                    <div class='response-effectiveness mt-3'></div>
                </div>
            </div>";
        });

        $this->component('smart_suggestions', function($context) {
            $html = "<div class='smart-suggestions mt-2'>";
            if (!empty($context['suggestions'])) {
                foreach ($context['suggestions'] as $suggestion) {
                    $html .= "<div class='suggestion-chip' 
                                  data-confidence='{$suggestion['confidence']}'
                                  data-category='{$suggestion['category']}'>
                        {$suggestion['text']}
                    </div>";
                }
            }
            return $html . '</div>';
        });

        $this->component('chat_insights', function($data) {
            return "<div class='chat-insights p-3'>
                <div class='customer-intent mb-2'>
                    <strong>Intent:</strong> {$data['intent']}
                </div>
                <div class='satisfaction-score'>
                    <strong>CSAT Prediction:</strong>
                    <div class='progress'>
                        <div class='progress-bar' style='width: {$data['csat_score']}%'></div>
                    </div>
                </div>
            </div>";
        });

        $this->component('response_templates', function($templates) {
            $html = "<div class='response-templates'>";
            foreach ($templates as $template) {
                $html .= "<div class='template-item' 
                              data-template-id='{$template['id']}'
                              data-context='{$this->escape(json_encode($template['context']))}'>
                    <div class='template-preview'>{$this->truncate($template['content'], 100)}</div>
                    <div class='template-meta'>
                        <span class='success-rate'>{$template['success_rate']}% success</span>
                        <span class='usage-count'>{$template['usage_count']} uses</span>
                    </div>
                </div>";
            }
            return $html . '</div>';
        });

        $this->component('ai_model_status', function($metrics) {
            return "<div class='ai-model-status card'>
                <div class='card-header d-flex justify-content-between'>
                    <h6 class='mb-0'>AI Model Performance</h6>
                    <div class='d-flex align-items-center'>
                        <span class='model-health me-2' data-status='{$metrics['health_status']}'>
                            <i class='fas fa-circle'></i>
                        </span>
                        <span class='model-version'>{$metrics['model_version']}</span>
                    </div>
                </div>
                <div class='card-body'>
                    <div class='accuracy-score'>
                        <label>Response Accuracy</label>
                        <div class='progress'>
                            <div class='progress-bar' style='width: {$metrics['accuracy']}%'></div>
                        </div>
                    </div>
                    <div class='performance-metrics mt-3'>
                        <div class='metric'>
                            <span>Avg. Response Time:</span>
                            <strong>{$metrics['avg_response_time']}ms</strong>
                        </div>
                        <div class='metric'>
                            <span>Success Rate:</span>
                            <strong>{$metrics['success_rate']}%</</strong>
                        </div>
                        <div class='metric'>
                            <span>Error Rate:</span>
                            <strong>{$metrics['error_rate']}%</</strong>
                        </div>
                    </div>
                    <div class='error-breakdown mt-3'>
                        <h6>Error Distribution</h6>
                        <div class='error-types'>
                            {$this->renderErrorBreakdown($metrics['error_types'])}
                        </div>
                    </div>
                    <div class='performance-trend mt-3'>
                        <h6>7-Day Trend</h6>
                        <div class='trend-chart' data-values='{$this->escape(json_encode($metrics['trend']))}'>
                        </div>
                    </div>
                    <div class='optimization-metrics mt-3'>
                        <h6>Optimization Status</h6>
                        <div class='metric-row'>
                            <span>Cache Hit Rate:</span>
                            <strong>{$metrics['cache_hit_rate']}%</strong>
                        </div>
                        <div class='metric-row'>
                            <span>Model Load:</span>
                            <strong>{$metrics['model_load']}%</</strong>
                        </div>
                        <div class='load-balancer-status'>
                            <span>Load Balancer:</span>
                            <strong class='status-{$metrics['lb_status']}'>{$metrics['lb_status']}</strong>
                        </div>
                    </div>
                    <div class='resource-usage mt-3'>
                        <h6>Resource Usage</h6>
                        <div class='metric-row'>
                            <span>Memory:</span>
                            <strong>{$metrics['memory_usage']}MB</strong>
                        </div>
                        <div class='metric-row'>
                            <span>GPU Utilization:</span>
                            <strong>{$metrics['gpu_utilization']}%</strong>
                        </div>
                        <div class='system-health mt-3'>
                            <h6>System Health</h6>
                            <div class='health-indicators'>
                                <div class='indicator {$metrics['cpu_health']}'>
                                    <span>CPU</span>
                                    <i class='fas fa-circle'></i>
                                </div>
                                <div class='indicator {$metrics['memory_health']}'>
                                    <span>Memory</span>
                                    <i class='fas fa-circle'></i>
                                </div>
                                <div class='indicator {$metrics['network_health']}'>
                                    <span>Network</span>
                                    <i class='fas fa-circle'></i>
                                </div>
                            </div>
                            <div class='real-time-stats' data-refresh='5000'>
                                <div class='stat'>
                                    <label>Requests/sec</label>
                                    <strong>{$metrics['requests_per_second']}</strong>
                                </div>
                                <div class='stat'>
                                    <label>Avg. Latency</label>
                                    <strong>{$metrics['avg_latency']}ms</strong>
                                </div>
                                <div class='stat'>
                                    <label>Active Workers</label>
                                    <strong>{$metrics['active_workers']}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>";
        });

        private function renderErrorBreakdown(array $error_types): string {
            $html = '';
            foreach ($error_types as $type => $percentage) {
                $html .= "<div class='error-type'>
                    <span class='type-name'>{$type}</span>
                    <div class='progress'>
                        <div class='progress-bar bg-danger' style='width: {$percentage}%'></div>
                    </div>
                    <span class='percentage'>{$percentage}%</span>
                </div>";
            }
            return $html;
        }

        $this->component('suggestion_quality', function($suggestion) {
            $quality_class = $this->getQualityClass($suggestion['quality_score']);
            return "<div class='suggestion-quality {$quality_class}'>
                <div class='quality-indicator'>
                    <i class='fas fa-check-circle'></i>
                    <span class='score'>{$suggestion['quality_score']}%</</span>
                </div>
                <div class='quality-metrics'>
                    <div class='relevance'>Relevance: {$suggestion['relevance']}%</</div>
                    <div class='coherence'>Coherence: {$suggestion['coherence']}%</</div>
                    <div class='tone'>Tone Match: {$suggestion['tone_match']}%</</div>
                </div>
            </div>";
        });

        $this->component('model_training_metrics', function($training_data) {
            return "<div class='model-training-metrics card mt-3'>
                <div class='card-header'>
                    <h6 class='mb-0'>Model Training Stats</h6>
                </div>
                <div class='card-body'>
                    <div class='training-progress'>
                        <label>Training Progress</label>
                        <div class='progress'>
                            <div class='progress-bar' style='width: {$training_data['progress']}%'></div>
                        </div>
                    </div>
                    <div class='training-stats mt-3'>
                        <div class='stat-item'>
                            <span>Epochs:</span>
                            <strong>{$training_data['epochs_completed']}/{$training_data['total_epochs']}</strong>
                        </div>
                        <div class='stat-item'>
                            <span>Loss:</span>
                            <strong>{$training_data['current_loss']}</strong>
                        </div>
                        <div class='stat-item'>
                            <span>Dataset Size:</span>
                            <strong>{$training_data['dataset_size']} samples</strong>
                        </div>
                    </div>
                </div>
            </div>";
        });

        $this->component('model_usage_stats', function($usage) {
            return "<div class='model-usage-stats p-3'>
                <div class='usage-header d-flex justify-content-between'>
                    <h6>Model Usage</h6>
                    <span class='period'>{$usage['period']}</span>
                </div>
                <div class='usage-metrics'>
                    <div class='metric-row'>
                        <span>Total Requests:</span>
                        <strong>{$usage['total_requests']}</strong>
                    </div>
                    <div class='metric-row'>
                        <span>Avg. Processing Time:</span>
                        <strong>{$usage['avg_processing_time']}ms</strong>
                    </div>
                    <div class='metric-row'>
                        <span>Success Rate:</span>
                        <strong>{$usage['success_rate']}%</</strong>
                    </div>
                    <div class='metric-row'>
                        <span>Token Usage:</span>
                        <strong>{$usage['token_usage']}</strong>
                    </div>
                </div>
            </div>";
        });
    }

    private function getSentimentClass(string $sentiment): string {
        return [
            'positive' => 'sentiment-positive',
            'negative' => 'sentiment-negative',
            'neutral' => 'sentiment-neutral'
        ][$sentiment] ?? 'sentiment-neutral';
    }

    private function truncate(string $text, int $length): string {
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }

    private function formatDate(string $date): string {
        return date('M j, Y g:i a', strtotime($date));
    }

    public function component(string $name, callable $callback): void {
        $this->components[$name] = $callback;
    }

    public function slot(string $name, $content = null): string {
        if ($content !== null) {
            $this->slots[$name] = $content;
            return '';
        }
        return $this->slots[$name] ?? '';
    }

    public function fragment(string $name, callable $callback): void {
        ob_start();
        $callback($this);
        $this->fragments[$name] = ob_get_clean();
    }

    public function renderFragment(string $name): string {
        return $this->fragments[$name] ?? '';
    }

    public function parent(): ?self {
        return $this->parent;
    }

    public function compose(string $template, callable $callback): void {
        $this->composers[$template] = $callback;
    }

    public function render(string $template, array $data = []): string {
        // Check for template override
        if (isset($this->overrides[$template])) {
            $template = $this->overrides[$template];
        }

        $data = array_merge($this->shared, $data);
        
        if ($this->debug) {
            $this->data['_template'] = $template;
            $this->data['_render_time'] = microtime(true);
        }
        // Run composers before rendering
        if (isset($this->composers[$template])) {
            $data = call_user_func($this->composers[$template], $data) ?: $data;
        }
        // Check cache first with expiration
        $cache_key = $template . '_' . md5(serialize($data));
        if (isset($this->cache[$cache_key]) && 
            $this->cache[$cache_key]['expires'] > time()) {
            return $this->cache[$cache_key]['content'];
        }

        $file = $this->template_path . $template . '.php';
        if (!file_exists($file)) {
            throw new \Exception("Template file not found: {$file}");
        }

        $this->data = array_merge($this->data, $this->sanitizeData($data));
        $this->data['view'] = $this; // Make engine available in templates
        extract($this->data);
        
        ob_start();
        include $file;
        $content = ob_get_clean();

        // Cache with expiration
        $this->cache[$cache_key] = [
            'content' => $content,
            'expires' => time() + $this->cache_expiration
        ];

        if ($this->debug) {
            $render_time = microtime(true) - $this->data['_render_time'];
            $debug_info = sprintf(
                '<!-- Template: %s, Render Time: %.4f seconds -->',
                $template,
                $render_time
            );
            $content .= $debug_info;
        }

        // Run middleware before returning
        return $this->runMiddleware($content);
    }

    private function sanitizeData(array $data): array {
        return array_map(function($value) {
            if (is_string($value)) {
                return wp_kses_post($value);
            }
            return $value;
        }, $data);
    }

    public function insert(string $template, array $data = []): string {
        return $this->render($template, $data);
    }

    public function layout(string $template, array $data = []): void {
        $this->sections['layout'] = $template;
        $this->data = array_merge($this->data, $data);
    }

    public function section(string $name): void {
        array_push($this->section_stack, $name);
        ob_start();
    }

    public function end(): void {
        $name = array_pop($this->section_stack);
        if ($name === null) {
            throw new \Exception('No section started');
        }
        $this->sections[$name] = ob_get_clean();
    }

    public function yield(string $section): string {
        return $this->sections[$section] ?? '';
    }

    // Add helper methods for templates
    public function escape($value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function asset(string $path): string {
        $version = isset($this->data['dev_mode']) ? time() : WPPS_VERSION;
        return $this->data['assets_url'] . ltrim($path, '/') . '?v=' . $version;
    }

    public function partial(string $name, array $data = []): string {
        return $this->render('partials/' . $name, $data);
    }

    public function push(string $stack, string $content): void {
        if (!isset($this->stacks[$stack])) {
            $this->stacks[$stack] = [];
        }
        $this->stacks[$stack][] = $content;
    }

    public function prepend(string $stack, string $content): void {
        if (!isset($this->stacks[$stack])) {
            $this->stacks[$stack] = [];
        }
        array_unshift($this->stacks[$stack], $content);
    }

    public function append(string $stack, string $content): void {
        if (!isset($this->stacks[$stack])) {
            $this->stacks[$stack][] = $content;
        }
        $this->stacks[$stack][] = $content;
    }

    public function stack(string $name): string {
        if (!isset($this->stacks[$name])) {
            return '';
        }
        return implode("\n", $this->stacks[$name]);
    }

    public function flash(string $message, string $type = 'info'): void {
        $this->flash[] = [
            'message' => $message,
            'type' => $type
        ];
        set_transient('wpps_flash_messages', $this->flash, 30);
    }

    public function flashNow(string $message, string $type = 'info', string $category = 'default'): void {
        if (!isset($this->flash[$category])) {
            $this->flash[$category] = [];
        }
        $this->flash[$category][] = [
            'message' => $message,
            'type' => $type,
            'time' => time()
        ];
    }

    public function getFlash(): array {
        return $this->flash;
    }

    public function extends(string $template): void {
        $this->parent = clone $this;
        $this->parent->render($template, $this->data);
        ob_start();
    }

    public function share(string $key, $value): void {
        $this->shared[$key] = $value;
    }

    public function __($text): string {
        return __($text, 'wp-woocommerce-printify-sync');
    }

    public function _e($text): void {
        _e($text, 'wp-woocommerce-printify-sync');
    }

    private function compile(string $template): string {
        $compiled_file = $this->compiled_path . '/' . md5($template) . '.php';
        
        try {
            if (!$this->debug && file_exists($compiled_file)) {
                return $compiled_file;
            }

            $content = file_get_contents($template);
            $content = $this->stripWhitespace($content);
            $content = $this->compileComments($content);
            $content = $this->compileInline($content);
            $content = $this->compileNestedDirectives($content);
            $content = $this->compileAttributes($content);
            $content = $this->compileInterpolation($content);
            $content = $this->compileSwitch($content);
            $content = $this->compileRaw($content);
            $content = $this->compileEchos($content);
            $content = $this->compilePhp($content);
            $content = $this->compileComponents($content);
            $content = $this->compileDirectives($content);
            
            wp_mkdir_p(dirname($compiled_file));
            file_put_contents($compiled_file, $content);
            
            return $compiled_file;
        } catch (\Exception $e) {
            if ($this->debug) {
                throw $e;
            }
            error_log("Template compilation error: " . $e->getMessage());
            return $template;
        }
    }

    private function stripWhitespace(string $content): string {
        return preg_replace('/\s*@(end\w+)/', '@$1', $content);
    }

    private function compileInline(string $content): string {
        return preg_replace('/@\((.*?)\)/', '<?php echo $1; ?>', $content);
    }

    private function compileNestedDirectives(string $content): string {
        $stack = [];
        $current = '';
        
        foreach (token_get_all($content) as $token) {
            if (is_array($token)) {
                if ($token[0] === T_INLINE_HTML) {
                    $current .= $this->compileDirectives($token[1]);
                } else {
                    $current .= $token[1];
                }
            } else {
                $current .= $token;
            }
        }
        
        return $current;
    }

    private function compileComments(string $content): string {
        return preg_replace('/\{\{--(.+?)--\}\}/s', '<?php /* $1 */ ?>', $content);
    }

    private function compileRaw(string $content): string {
        return preg_replace('/\{!!(.+?)!!\}/', '<?php echo $1; ?>', $content);
    }

    private function compileEchos(string $content): string {
        // Compile filtered echoes
        $pattern = '/\{\{(.+?)\|(.+?)\}\}/';
        $content = preg_replace_callback($pattern, function($matches) {
            $filters = explode('|', trim($matches[2]));
            $value = trim($matches[1]);
            $code = "<?php \$_tmp = {$value};";
            foreach ($filters as $filter) {
                if (strpos($filter, ':') !== false) {
                    [$filter, $args] = explode(':', $filter);
                    $code .= "\$_tmp = \$this->applyFilter(\$_tmp, '{$filter}', {$args});";
                } else {
                    $code .= "\$_tmp = \$this->applyFilter(\$_tmp, '{$filter}');";
                }
            }
            $code .= "echo \$this->escape(\$_tmp);?>";
            return $code;
        }, $content);

        // Compile regular echoes
        return preg_replace('/\{\{(.+?)\}\}/', '<?php echo $this->escape($1); ?>', $content);
    }

    private function compilePhp(string $content): string {
        // Compile PHP blocks
        $content = preg_replace('/@php(.+?)@endphp/s', '<?php$1?>', $content);
        
        // Compile unless blocks
        $content = preg_replace('/@unless\s*\((.+?)\)/', '<?php if(! ($1)): ?>', $content);
        $content = str_replace('@endunless', '<?php endif; ?>', $content);
        
        // Compile loop shorthand
        $content = preg_replace('/@loop\s*\((.+?)\)/', '<?php foreach($1 as $key => $value): ?>', $content);
        $content = str_replace('@endloop', '<?php endforeach; ?>', $content);

        return $content;
    }

    private function compileDirectives(string $content): string {
        foreach ($this->directives as $name => $callback) {
            $pattern = "/\@{$name}(\s*\(.*\))/";
            $content = preg_replace_callback($pattern, function($matches) use ($callback) {
                return $callback($matches[1]);
            }, $content);
        }
        return $content;
    }

    private function compileAttributes(string $content): string {
        return preg_replace_callback('/@attr\((.*?)\)/', function($matches) {
            $attrs = trim($matches[1]);
            return "<?php echo \$this->formatAttributes({$attrs}); ?>";
        }, $content);
    }

    private function compileInterpolation(string $content): string {
        return preg_replace('/\${(.*?)}/', '<?php echo $this->escape($1); ?>', $content);
    }

    private function compileSwitch(string $content): string {
        $content = preg_replace('/@switch\s*\((.*?)\)/', '<?php switch($1):', $content);
        $content = preg_replace('/@case\s*\((.*?)\)/', '<?php case $1:', $content);
        $content = preg_replace('/@default/', '<?php default:', $content);
        $content = str_replace('@break', '<?php break;', $content);
        $content = str_replace('@endswitch', '<?php endswitch; ?>', $content);
        return $content;
    }

    private function compileComponents(string $content): string {
        return preg_replace_callback('/@component\s*\([\'"](.*?)[\'"]\s*(,.*?)?\)/', function($matches) {
            $component = $matches[1];
            $args = isset($matches[2]) ? trim($matches[2], ' ,') : '';
            return "<?php echo \$this->renderComponent('{$component}', [{$args}]); ?>";
        }, $content);
    }

    public function renderComponent(string $name, array $data = []): string {
        if (!isset($this->components[$name])) {
            return '';
        }
        return call_user_func_array($this->components[$name], $data);
    }

    public function formatAttributes(array $attributes): string {
        return implode(' ', array_map(function($key, $value) {
            if (is_bool($value)) {
                return $value ? $key : '';
            }
            return sprintf('%s="%s"', $key, $this->escape($value));
        }, array_keys($attributes), $attributes));
    }

    public function addNamespace(string $namespace, string $path): void {
        $this->namespaces[$namespace] = rtrim($path, '/');
    }

    public function filter(string $name, callable $callback): void {
        $this->filters[$name] = $callback;
    }

    public function macro(string $name, callable $callback): void {
        $this->macros[$name] = $callback;
    }

    public function directive(string $name, callable $callback): void {
        $this->directives[$name] = $callback;
    }

    private function registerDefaultDirectives(): void {
        $this->directive('if', fn($expression) => "<?php if{$expression}: ?>");
        $this->directive('endif', fn() => "<?php endif; ?>");
        $this->directive('foreach', fn($expression) => "<?php foreach{$expression}: ?>");
        $this->directive('endforeach', fn() => "<?php endforeach; ?>");
    }

    private function registerDefaultFilters(): void {
        $this->filter('upper', 'strtoupper');
        $this->filter('lower', 'strtolower');
        $this->filter('trim', 'trim');
        $this->filter('nl2br', 'nl2br');
    }

    public function __call($method, $args) {
        if (isset($this->macros[$method])) {
            return call_user_func_array($this->macros[$method], $args);
        }
        throw new \BadMethodCallException("Method {$method} does not exist");
    }

    public function raw($value): string {
        return $value;
    }

    public function when($condition, callable $callback): self {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }

    public function unless($condition, callable $callback): self {
        if (!$condition) {
            $callback($this);
        }
        return $this;
    }

    public function loop(array $data, callable $callback): void {
        foreach ($data as $key => $value) {
            $callback($value, $key);
        }
    }

    public function includeWhen(bool $condition, string $template, array $data = []): string {
        return $condition ? $this->insert($template, $data) : '';
    }

    public function includeFirst(array $templates, array $data = []): string {
        foreach ($templates as $template) {
            if (file_exists($this->template_path . $template . '.php')) {
                return $this->insert($template, $data);
            }
        }
        return '';
    }

    public function applyFilter(string $value, string $filter, ...$args): string {
        if (!isset($this->filters[$filter])) {
            return $value;
        }
        return call_user_func_array($this->filters[$filter], [$value, ...$args]);
    }

    public function addMiddleware(callable $middleware): void {
        $this->middleware[] = $middleware;
    }

    public function override(string $template, string $with): void {
        $this->overrides[$template] = $with;
    }

    private function runMiddleware(string $content): string {
        foreach ($this->middleware as $middleware) {
            $content = $middleware($content);
        }
        return $content;
    }

    public function trackError(string $source, string $message): void {
        $this->errors[] = [
            'source' => $source,
            'message' => $message,
            'time' => current_time('mysql')
        ];
    }

    public function setSyncStatus(string $type, string $status, ?string $message = null): void {
        $this->sync_status[$type] = [
            'status' => $status,
            'message' => $message,
            'updated_at' => current_time('mysql')
        ];
    }

    public function getSyncStatus(string $type): ?array {
        return $this->sync_status[$type] ?? null;
    }

    public function hasSyncErrors(): bool {
        return !empty($this->errors);
    }

    public function getSyncErrors(): array {
        return $this->errors;
    }

    public function clearSyncErrors(): void {
        $this->errors = [];
    }

    private function getQualityClass(int $score): string {
        return match(true) {
            $score >= 90 => 'quality-excellent',
            $score >= 75 => 'quality-good',
            $score >= 60 => 'quality-fair',
            default => 'quality-poor'
        };
    }
}
