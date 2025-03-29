<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Tickets;

class Ticket
{
    private int $id;
    private string $subject;
    private string $message;
    private string $status;
    private int $orderId;
    private array $responses;
    private int $userId;
    private string $createdAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? 0;
        $this->subject = $data['subject'] ?? '';
        $this->message = $data['message'] ?? '';
        $this->status = $data['status'] ?? 'open';
        $this->orderId = (int)($data['order_id'] ?? 0);
        $this->responses = $data['responses'] ?? [];
        $this->userId = (int)($data['user_id'] ?? 0);
        $this->createdAt = $data['created_at'] ?? current_time('mysql');
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status,
            'order_id' => $this->orderId,
            'responses' => $this->responses,
            'user_id' => $this->userId,
            'created_at' => $this->createdAt
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getResponses(): array
    {
        return $this->responses;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function addResponse(string $response, int $userId): void
    {
        $this->responses[] = [
            'message' => $response,
            'user_id' => $userId,
            'created_at' => current_time('mysql')
        ];
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}