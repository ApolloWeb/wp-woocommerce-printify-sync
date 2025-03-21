<?php
/**
 * Action Scheduler Service.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Action Scheduler Service for scheduling background tasks.
 */
class ActionSchedulerService
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Initialize the service.
     *
     * @return void
     */
    public function init()
    {
        // Ensure Action Scheduler is available
        if (!class_exists('ActionScheduler')) {
            $this->logger->error('Action Scheduler not found. Some features will not work.');
            return;
        }

        // Register custom actions
        add_action('wpwps_as_sync_all_products', [$this, 'handleSyncAllProducts']);
        add_action('wpwps_as_sync_product', [$this, 'handleSyncProduct'], 10, 1);
        add_action('wpwps_as_sync_all_orders', [$this, 'handleSyncAllOrders']);
        add_action('wpwps_as_sync_order', [$this, 'handleSyncOrder'], 10, 1);
    }

    /**
     * Schedule a single product sync.
     *
     * @param string $product_id Printify product ID.
     * @param int    $timestamp  Optional timestamp for when to run the action.
     * @return int Action ID.
     */
    public function scheduleSyncProduct($product_id, $timestamp = 0)
    {
        $timestamp = $timestamp ? $timestamp : time();
        
        // Check if there's already a scheduled action for this product
        $existing_actions = as_get_scheduled_actions([
            'hook' => 'wpwps_as_sync_product',
            'args' => [$product_id],
            'status' => 'pending',
        ]);
        
        if (!empty($existing_actions)) {
            // Action already scheduled, just return the existing ID
            $action = reset($existing_actions);
            return $action->get_id();
        }
        
        $this->logger->info("Scheduling sync for product {$product_id}");
        
        return as_schedule_single_action(
            $timestamp,
            'wpwps_as_sync_product',
            [$product_id],
            'wpwps'
        );
    }

    /**
     * Schedule a full product sync.
     *
     * @param int $timestamp Optional timestamp for when to run the action.
     * @return int Action ID.
     */
    public function scheduleSyncAllProducts($timestamp = 0)
    {
        $timestamp = $timestamp ? $timestamp : time();
        
        // Check if there's already a scheduled action
        $existing_actions = as_get_scheduled_actions([
            'hook' => 'wpwps_as_sync_all_products',
            'status' => 'pending',
        ]);
        
        if (!empty($existing_actions)) {
            // Action already scheduled, just return the existing ID
            $action = reset($existing_actions);
            return $action->get_id();
        }
        
        $this->logger->info("Scheduling sync for all products");
        
        return as_schedule_single_action(
            $timestamp,
            'wpwps_as_sync_all_products',
            [],
            'wpwps'
        );
    }

    /**
     * Schedule a single order sync.
     *
     * @param string $order_id  Printify order ID.
     * @param int    $timestamp Optional timestamp for when to run the action.
     * @return int Action ID.
     */
    public function scheduleSyncOrder($order_id, $timestamp = 0)
    {
        $timestamp = $timestamp ? $timestamp : time();
        
        // Check if there's already a scheduled action for this order
        $existing_actions = as_get_scheduled_actions([
            'hook' => 'wpwps_as_sync_order',
            'args' => [$order_id],
            'status' => 'pending',
        ]);
        
        if (!empty($existing_actions)) {
            // Action already scheduled, just return the existing ID
            $action = reset($existing_actions);
            return $action->get_id();
        }
        
        $this->logger->info("Scheduling sync for order {$order_id}");
        
        return as_schedule_single_action(
            $timestamp,
            'wpwps_as_sync_order',
            [$order_id],
            'wpwps'
        );
    }

    /**
     * Schedule a full order sync.
     *
     * @param int $timestamp Optional timestamp for when to run the action.
     * @return int Action ID.
     */
    public function scheduleSyncAllOrders($timestamp = 0)
    {
        $timestamp = $timestamp ? $timestamp : time();
        
        // Check if there's already a scheduled action
        $existing_actions = as_get_scheduled_actions([
            'hook' => 'wpwps_as_sync_all_orders',
            'status' => 'pending',
        ]);
        
        if (!empty($existing_actions)) {
            // Action already scheduled, just return the existing ID
            $action = reset($existing_actions);
            return $action->get_id();
        }
        
        $this->logger->info("Scheduling sync for all orders");
        
        return as_schedule_single_action(
            $timestamp,
            'wpwps_as_sync_all_orders',
            [],
            'wpwps'
        );
    }

    /**
     * Handle the sync all products action.
     *
     * @return void
     */
    public function handleSyncAllProducts()
    {
        $this->logger->info("Starting sync for all products");
        
        // Trigger the product sync action
        do_action('wpwps_sync_products');
    }

    /**
     * Handle the sync product action.
     *
     * @param string $product_id Printify product ID.
     * @return void
     */
    public function handleSyncProduct($product_id)
    {
        $this->logger->info("Starting sync for product {$product_id}");
        
        // Trigger the single product sync action
        do_action('wpwps_sync_single_product', $product_id);
    }

    /**
     * Handle the sync all orders action.
     *
     * @return void
     */
    public function handleSyncAllOrders()
    {
        $this->logger->info("Starting sync for all orders");
        
        // Trigger the order sync action
        do_action('wpwps_sync_orders');
    }

    /**
     * Handle the sync order action.
     *
     * @param string $order_id Printify order ID.
     * @return void
     */
    public function handleSyncOrder($order_id)
    {
        $this->logger->info("Starting sync for order {$order_id}");
        
        // Trigger the single order sync action
        do_action('wpwps_sync_single_order', $order_id);
    }

    /**
     * Get pending actions count.
     *
     * @param string $hook Optional hook name to filter by.
     * @return int Number of pending actions.
     */
    public function getPendingActionsCount($hook = '')
    {
        $args = [
            'status' => 'pending',
            'group' => 'wpwps',
        ];
        
        if ($hook) {
            $args['hook'] = $hook;
        }
        
        return as_get_scheduled_actions_count($args);
    }

    /**
     * Get a list of pending actions.
     *
     * @param string $hook  Optional hook name to filter by.
     * @param int    $limit Optional limit of actions to return.
     * @return array List of pending actions.
     */
    public function getPendingActions($hook = '', $limit = 10)
    {
        $args = [
            'status' => 'pending',
            'group' => 'wpwps',
            'per_page' => $limit,
        ];
        
        if ($hook) {
            $args['hook'] = $hook;
        }
        
        $actions = as_get_scheduled_actions($args);
        $result = [];
        
        foreach ($actions as $action) {
            $result[] = [
                'id' => $action->get_id(),
                'hook' => $action->get_hook(),
                'args' => $action->get_args(),
                'scheduled_date' => $action->get_schedule()->get_date(),
            ];
        }
        
        return $result;
    }
}
