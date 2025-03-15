<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class TaskList
{
    private string $currentTime = '2025-03-15 19:58:18';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_update_task_status', [$this, 'updateTaskStatus']);
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'printify-sync',
            'Setup Tasks',
            'Setup Tasks',
            'manage_woocommerce',
            'printify-setup',
            [$this, 'renderPage']
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'printify-sync_page_printify-setup') {
            return;
        }

        wp_enqueue_style(
            'wpwps-tasks',
            plugins_url('assets/css/tasks.css', WPWPS_PLUGIN_FILE)
        );

        wp_enqueue_script(
            'wpwps-tasks',
            plugins_url('assets/js/tasks.js', WPWPS_PLUGIN_FILE),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('wpwps-tasks', 'wpwps', [
            'nonce' => wp_create_nonce('wpwps-tasks'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }

    public function renderPage(): void
    {
        $tasks = $this->getTasks();
        ?>
        <div class="wpwps-tasks">
            <div class="tasks-header">
                <h1>Printify Integration Setup</h1>
                <div class="progress-indicator">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $this->calculateProgress($tasks); ?>%"></div>
                    </div>
                    <span class="progress-text"><?php echo $this->calculateProgress($tasks); ?>% Complete</span>
                </div>
            </div>

            <div class="tasks-grid">
                <?php foreach ($tasks as $taskId => $task): ?>
                    <div class="task-card <?php echo $task['status']; ?>" data-task-id="<?php echo $taskId; ?>">
                        <div class="task-header">
                            <div class="task-icon">
                                <span class="<?php echo $task['icon']; ?>"></span>
                            </div>
                            <h2><?php echo $task['title']; ?></h2>
                            <?php if ($task['required']): ?>
                                <span class="required-badge">Required</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="task-content">
                            <p><?php echo $task['description']; ?></p>
                            
                            <?php if (!empty($task['steps'])): ?>
                                <div class="task-steps">
                                    <?php foreach ($task['steps'] as $step): ?>
                                        <div class="step">
                                            <span class="step-number"><?php echo $step['number']; ?></span>
                                            <span class="step-text"><?php echo $step['text']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="task-actions">
                            <?php if ($task['link']): ?>
                                <a href="<?php echo $task['link']; ?>" class="task-button">
                                    <?php echo $task['button_text']; ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($task['status'] === 'pending' && $task['can_complete']): ?>
                                <button class="complete-task" data-task="<?php echo $taskId; ?>">
                                    Mark Complete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function getTasks(): array
    {
        return [
            'webhook' => [
                'title' => 'Configure Webhook',
                'description' => 'Set up the Printify webhook to receive product updates automatically.',
                'icon' => 'dashicons dashicons-rest-api',
                'status' => $this->getTaskStatus('webhook'),
                'required' => true,
                'can_complete' => false,
                'link' => admin_url('admin.php?page=printify-webhook'),
                'button_text' => 'Configure Webhook',
                'steps' => [
                    ['number' => '1', 'text' => 'Go to Printify Dashboard → Settings → Webhooks'],
                    ['number' => '2', 'text' => 'Add new webhook with the URL provided'],
                    ['number' => '3', 'text' => 'Copy the secret key and save it in the webhook settings']
                ]
            ],
            'products' => [
                'title' => 'Import Initial Products',
                'description' => 'Perform initial product import from Printify to WooCommerce.',
                'icon' => 'dashicons dashicons-products',
                'status' => $this->getTaskStatus('products'),
                'required' => true,
                'can_complete' => true,
                'link' => admin_url('admin.php?page=printify-import'),
                'button_text' => 'Start Import'
            ],
            'images' => [
                'title' => 'Configure Image Storage',
                'description' => 'Set up image storage settings for product images.',
                'icon' => 'dashicons dashicons-images-alt2',
                'status' => $this->getTaskStatus('images'),
                'required' => false,
                'can_complete' => true,
                'link' => admin_url('admin.php?page=printify-settings'),
                'button_text' => 'Configure Storage'
            ],
            'test' => [
                'title' => 'Test Integration',
                'description' => 'Verify the webhook is working by updating a product in Printify.',
                'icon' => 'dashicons dashicons-yes-alt',
                'status' => $this->getTaskStatus('test'),
                'required' => true,
                'can_complete' => true,
                'steps' => [
                    ['number' => '1', 'text' => 'Edit any product in Printify'],
                    ['number' => '2', 'text' => 'Save the changes'],
                    ['number' => '3', 'text' => 'Verify the update appears in WooCommerce']
                ]
            ]
        ];
    }

    private function getTaskStatus(string $taskId): string
    {
        $statuses = get_option('wpwps_task_statuses', []);
        return $statuses[$taskId] ?? 'pending';
    }

    private function calculateProgress(array $tasks): int
    {
        $completed = 0;
        $total = 0;

        foreach ($tasks as $task) {
            if ($task['required']) {
                $total++;
                if ($task['status'] === 'completed') {
                    $completed++;
                }
            }
        }

        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }

    public function updateTaskStatus(): void
    {
        check_ajax_referer('wpwps-tasks');

        $taskId = sanitize_text_field($_POST['task_id']);
        $status = sanitize_text_field($_POST['status']);

        $statuses = get_option('wpwps_task_statuses', []);
        $statuses[$taskId] = $status;
        update_option('wpwps_task_statuses', $statuses);

        wp_send_json_success([
            'message' => 'Task status updated',
            'progress' => $this->calculateProgress($this->getTasks())
        ]);
    }
}