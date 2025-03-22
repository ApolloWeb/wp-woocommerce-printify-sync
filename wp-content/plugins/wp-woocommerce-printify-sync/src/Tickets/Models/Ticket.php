<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Tickets\Models;

class Ticket {
    const STATUS_OPEN = 'open';
    const STATUS_PENDING = 'pending';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    private $id;
    private $subject;
    private $description;
    private $status;
    private $user_id;
    private $created_at;
    private $updated_at;
    private $responses = [];

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->subject = $data['subject'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->status = $data['status'] ?? self::STATUS_OPEN;
        $this->user_id = $data['user_id'] ?? get_current_user_id();
        $this->created_at = $data['created_at'] ?? current_time('mysql');
        $this->updated_at = $data['updated_at'] ?? current_time('mysql');
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'responses' => $this->responses
        ];
    }
}
