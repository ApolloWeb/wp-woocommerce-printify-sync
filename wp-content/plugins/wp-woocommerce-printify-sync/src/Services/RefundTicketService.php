<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class RefundTicketService
{
    use TimeStampTrait;

    // Custom statuses for WooCommerce
    public const STATUS_REFUND_REQUESTED = 'refund-requested';
    public const STATUS_REPRINT_REQUESTED = 'reprint-requested';
    public const STATUS_REFUND_APPROVED = 'refund-approved';
    public const STATUS_REPRINT_APPROVED = 'reprint-approved';
    public const STATUS_REFUND_DENIED = 'refund-denied';
    public const STATUS_AWAITING_EVIDENCE = 'awaiting-evidence';
    public const STATUS_EVIDENCE_SUBMITTED = 'evidence-submitted';

    private TicketingService $ticketing;
    private PrintifyAPIHandler $apiHandler;
    private LoggerInterface $logger;

    public function __construct(
        TicketingService $ticketing,
        PrintifyAPIHandler $apiHandler,
        LoggerInterface $logger
    ) {
        $this->ticketing = $ticketing;
        $this->apiHandler = $apiHandler;
        $this->logger = $logger;
    }

    public function createRefundTicket(array $data): int
    {
        $ticketData = [
            'title' => sprintf(
                'Refund Request - Order #%s',
                $data['order_id']
            ),
            'type' => 'refund_request',
            'priority' => 'high',
            'status' => self::STATUS_AWAITING_EVIDENCE,
            'metadata' => [
                'order_id' => $data['order_id'],
                'printify_order_id' => $data['printify_order_id'],
                'reason' => $data['reason'],
                'refund_type' => $data['refund_type'], // 'refund' or 'reprint'
                'amount' => $data['amount'] ?? 0,
                'customer_email' => $data['customer_email'],
                'evidence_required' => true
            ]
        ];

        $ticketId = $this->ticketing->createTicket($ticketData);

        // Update order status
        $order = wc_get_order($data['order_id']);
        if ($order) {
            $newStatus = $data['refund_type'] === 'refund' 
                ? self::STATUS_REFUND_REQUESTED 
                : self::STATUS_REPRINT_REQUESTED;
            
            $order->update_status(
                $newStatus,
                __('Refund/Reprint ticket created: #', 'wp-woocommerce-printify-sync') . $ticketId
            );
        }

        return $ticketId;
    }

    public function attachEvidence(int $ticketId, array $files): void
    {
        $ticket = $this->ticketing->getTicket($ticketId);
        if (!$ticket) {
            throw new \Exception('Ticket not found');
        }

        // Store files
        $evidence = [];
        foreach ($files as $file) {
            $evidence[] = [
                'path' => $file['path'],
                'type' => $file['type'],
                'description' => $file['description'] ?? ''
            ];
        }

        // Update ticket
        $this->ticketing->updateTicket($ticketId, [
            'status' => self::STATUS_EVIDENCE_SUBMITTED,
            'metadata' => array_merge($ticket['metadata'], ['evidence' => $evidence])
        ]);

        // Submit to Printify
        $this->submitToPrintify($ticket);
    }

    private function submitToPrintify(array $ticket): void
    {
        $metadata = $ticket['metadata'];
        
        try {
            // Prepare evidence files
            $evidence = array_map(function($item) {
                return [
                    'file' => fopen($item['path'], 'r'),
                    'description' => $item['description']
                ];
            }, $metadata['evidence'] ?? []);

            // Submit to Printify
            $response = $this->apiHandler->submitRefundRequest([
                'order_id' => $metadata['printify_order_id'],
                'reason' => $metadata['reason'],
                'type' => $metadata['refund_type'],
                'evidence' => $evidence,
                'amount' => $metadata['amount'],
                'ticket_id' => $ticket['id']
            ]);

            // Update ticket with Printify reference
            $this->ticketing->updateTicket($ticket['id'], [
                'metadata' => array_merge($metadata, [
                    'printify_claim_id' => $response['claim_id']
                ])
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to submit to Printify', $this->addTimeStampData([
                'ticket_id' => $ticket['id'],
                'error' => $e->getMessage()
            ]));
            throw $e;
        }
    }

    public function handlePrintifyResponse(array $webhookData): void
    {
        $ticket = $this->ticketing->findTicketByMeta('printify_claim_id', $webhookData['claim_id']);
        if (!$ticket) {
            throw new \Exception('No ticket found for claim');
        }

        $metadata = $ticket['metadata'];
        $order = wc_get_order($metadata['order_id']);
        
        if ($webhookData['status'] === 'approved') {
            if ($metadata['refund_type'] === 'refund') {
                // Handle refund
                $this->processRefund($order, $metadata['amount'], $ticket['id']);
            } else {
                // Handle reprint
                $this->processReprint($order, $metadata['printify_order_id'], $ticket['id']);
            }
        } else {
            // Handle denial
            $this->ticketing->updateTicket($ticket['id'], [
                'status' => self::STATUS_REFUND_DENIED,
                'metadata' => array_merge($metadata, [
                    'denial_reason' => $webhookData['reason']
                ])
            ]);

            $order->update_status(
                self::STATUS_REFUND_DENIED,
                sprintf(
                    __('Refund denied by Printify. Reason: %s', 'wp-woocommerce-printify-sync'),
                    $webhookData['reason']
                )
            );
        }
    }

    private function processRefund(\WC_Order $order, float $amount, int $ticketId): void
    {
        // Create WooCommerce refund
        wc_create_refund([
            'amount' => $amount,
            'reason' => sprintf(
                __('Printify refund - Ticket #%d', 'wp-woocommerce-printify-sync'),
                $ticketId
            ),
            'order_id' => $order->get_id()
        ]);

        $order->update_status(
            self::STATUS_REFUND_APPROVED,
            __('Refund approved by Printify', 'wp-woocommerce-printify-sync')
        );
    }

    private function processReprint(\WC_Order $order, string $printifyOrderId, int $ticketId): void
    {
        $order->update_status(
            self::STATUS_REPRINT_APPROVED,
            __('Reprint approved by Printify', 'wp-woocommerce-printify-sync')
        );

        // The reprint will come through normal order webhooks
        // but will be linked to original order
        $this->ticketing->updateTicket($ticketId, [
            'status' => self::STATUS_REPRINT_APPROVED
        ]);
    }
}