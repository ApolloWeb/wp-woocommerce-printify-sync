<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Email;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\EmailQueueInterface;
use PHPMailer\PHPMailer\PHPMailer;

class QueueManager implements EmailQueueInterface {
    private array $config;
    private string $table_name;

    public function __construct(array $config = []) 
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpwps_email_queue';
        $this->config = wp_parse_args($config, [
            'process_interval' => 300,
            'batch_size' => 50,
            'max_retries' => 3,
            'smtp' => [
                'host' => '',
                'port' => 587,
                'username' => '',
                'password' => '',
                'encryption' => 'tls',
                'from_email' => '',
                'from_name' => ''
            ]
        ]);
    }

    public function add(array $email): int 
    {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            [
                'to_email' => $email['to'],
                'subject' => $email['subject'],
                'body' => $email['body'],
                'headers' => maybe_serialize($email['headers'] ?? []),
                'attachments' => maybe_serialize($email['attachments'] ?? []),
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'attempts' => 0
            ]
        );

        return $wpdb->insert_id;
    }

    public function process(int $batch_size = 50): array 
    {
        global $wpdb;
        
        $results = [
            'processed' => 0,
            'failed' => 0,
            'success' => 0
        ];

        $emails = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE status = 'pending' 
                AND attempts < %d 
                ORDER BY created_at ASC 
                LIMIT %d",
                $this->config['max_retries'],
                $batch_size
            )
        );

        foreach ($emails as $email) {
            $results['processed']++;
            
            try {
                $this->sendEmail($email);
                $this->markAsComplete($email->id);
                $results['success']++;
            } catch (\Exception $e) {
                $this->markAsFailed($email->id, $e->getMessage());
                $results['failed']++;
            }
        }

        return $results;
    }

    private function sendEmail(\stdClass $email): bool 
    {
        $mailer = new PHPMailer(true);
        
        // Configure SMTP
        $mailer->isSMTP();
        $mailer->Host = $this->config['smtp']['host'];
        $mailer->Port = $this->config['smtp']['port'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $this->config['smtp']['username'];
        $mailer->Password = $this->config['smtp']['password'];
        $mailer->SMTPSecure = $this->config['smtp']['encryption'];
        
        // Set sender
        $mailer->setFrom(
            $this->config['smtp']['from_email'],
            $this->config['smtp']['from_name']
        );
        
        // Set recipient
        $mailer->addAddress($email->to_email);
        
        // Set content
        $mailer->Subject = $email->subject;
        $mailer->Body = $email->body;
        $mailer->isHTML(true);
        
        return $mailer->send();
    }

    public function getStats(): array 
    {
        global $wpdb;
        
        return [
            'queued' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'"),
            'sent' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'completed'"),
            'failed' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'failed'")
        ];
    }

    public function retry(int $email_id): bool 
    {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            ['status' => 'pending', 'attempts' => 0],
            ['id' => $email_id]
        ) !== false;
    }

    public function purgeOld(int $days = 30): int 
    {
        global $wpdb;
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }

    private function markAsComplete(int $email_id): void 
    {
        global $wpdb;
        
        $wpdb->update(
            $this->table_name,
            [
                'status' => 'completed',
                'completed_at' => current_time('mysql')
            ],
            ['id' => $email_id]
        );
    }

    private function markAsFailed(int $email_id, string $error): void 
    {
        global $wpdb;
        
        $wpdb->update(
            $this->table_name,
            [
                'status' => 'failed',
                'error' => $error,
                'attempts' => new \raw('attempts + 1')
            ],
            ['id' => $email_id]
        );
    }

    public function getQueuedCount(): int 
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s",
                'pending'
            )
        );
    }
}
