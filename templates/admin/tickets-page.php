<?php
/**
 * Tickets Page Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Sample data for tickets
$tickets = [
    [
        'id' => 'TKT-1025',
        'subject' => 'Product sync failing',
        'status' => 'open',
        'status_label' => 'Open',
        'date_created' => '2025-03-03 14:25:30',
        'last_updated' => '2025-03-04 08:45:22',
        'priority' => 'high',
        'assigned_to' => 'Support Team',
        'customer_name' => 'Jennifer Miller',
        'customer_email' => 'jennifer@example.com',
        'order_id' => '1085',
        'message' => 'I\'m trying to sync products from Printify but keep getting an error. Could you please help me troubleshoot this issue?',
        'category' => 'technical',
        'replies' => 2
    ],
    [
        'id' => 'TKT-1024',
        'subject' => 'How to customize product descriptions?',
        'status' => 'open',
        'status_label' => 'Open',
        'date_created' => '2025-03-02 09:30:15',
        'last_updated' => '2025-03-03 16:20:45',
        'priority' => 'medium',
        'assigned_to' => 'Documentation Team',
        'customer_name' => 'Michael Brown',
        'customer_email' => 'michael@example.com',
        'order_id' => null,
        'message' => 'I\'d like to customize the product descriptions that come from Printify. Is there a way to edit them in bulk?',
        'category' => 'general',
        'replies' => 1
    ],
    [
        'id' => 'TKT-1023',
        'subject' => 'Need help setting up tax rates',
        'status' => 'in_progress',
        'status_label' => 'In Progress',
        'date_created' => '2025-03-01 11:15:40',
        'last_updated' => '2025-03-03 10:45:22',
        'priority' => 'medium',
        'assigned_to' => 'Sales Team',
        'customer_name' => 'Sarah Johnson',
        'customer_email' => 'sarah@example.com',
        'order_id' => null,
        'message' => 'I need assistance setting up the correct tax rates for different countries. Can you provide guidance on this?',
        'category' => 'billing',
        'replies' => 3
    ],
    [
        'id' => 'TKT-1022',
        'subject' => 'Error in orders page',
        'status' => 'resolved',
        'status_label' => 'Resolved',
        'date_created' => '2025-02-28 17:05:10',
        'last_updated' => '2025-03-01 09:30:15',
        'priority' => 'high',
        'assigned_to' => 'Developer Team',
        'customer_name' => 'Robert Wilson',
        'customer_email' => 'robert@example.com',
        'order_id' => '1075',
        'message' => 'I\'m seeing an error on the orders page when trying to view order details. It says "undefined variable" when I click on the order.',
        'category' => 'technical',
        'replies' => 4
    ],
    [
        'id' => 'TKT-1021',
        'subject' => 'Feature request: bulk product import',
        'status' => 'resolved',
        'status_label' => 'Resolved',
        'date_created' => '2025-02-25 14:40:35',
        'last_updated' => '2025-02-27 16:15:50',
        'priority' => 'low',
        'assigned_to' => 'Product Team',
        'customer_name' => 'Lisa Garcia',
        'customer_email' => 'lisa@example.com',
        'order_id' => null,
        'message' => 'Would it be possible to add a feature for bulk importing products from a CSV file? This would save us a lot of time.',
        'category' => 'feature',
        'replies' => 2
    ]
];

// Get status counts
$status_counts = [
    'open' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'closed' => 0
];

foreach ($tickets as $ticket) {
    if (isset($status_counts[$ticket['status']])) {
        $status_counts[$ticket['status']]++;
    }
}

// Function to get ticket status class
function get_ticket_status_class($status) {
    switch ($status) {
        case 'open':
            return 'warning';
        case 'in_progress':
            return 'info';
        case 'resolved':
            return 'success';
        case 'closed':
            return 'primary';
        default:
            return '';
    }
}

// Function to get priority class
function get_priority_class($priority) {
    switch ($priority) {
        case 'high':
            return 'error';
        case 'medium':
            return 'warning';
        case 'low':
            return 'success';
        default:
            return 'info';
    }
}

// Function to get category class
function get_category_class($category) {
    switch ($category) {
        case 'technical':
            return 'tech';
        case 'billing':
            return 'billing';
        case 'feature':
            return 'feature';
        case 'refund':
            return 'refund';
        default:
            return 'general';
    }
}

// Get current user info
$current_user = function_exists('printify_sync_get_current_user') ? 
    printify_sync_get_current_user() : 'User';
    
$current_datetime = function_exists('printify_sync_get_current_datetime') ?
    printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');

// Sample ticket for viewing (first ticket in the list)
$view_ticket = $tickets[0];
?>

<div class="printify-dashboard-page">
    
    <?php 
    // Include the navigation
    if (file_exists(PRINTIFY_SYNC_PATH . 'templates/admin/navigation.php')) {
        include PRINTIFY_SYNC_PATH . 'templates/admin/navigation.php';
    }
    ?>
    
    <div class="printify-content">
        