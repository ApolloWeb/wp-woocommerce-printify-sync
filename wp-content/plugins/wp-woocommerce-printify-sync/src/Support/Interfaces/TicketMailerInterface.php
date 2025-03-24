<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support\Interfaces;

interface TicketMailerInterface {
    public function sendResponse(object $ticket, string $content, array $attachments = []): void;
    public function sendNotification(object $ticket, string $type, array $data = []): void;
    public function getTemplate(string $template): string;
}
