<?php
/**
 * Shipping Profiles functionality.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Shipping
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPIClient;
use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActivityService;
use WP_Error;

/**
 * Class for managing shipping profiles from Printify.
 */
class ShippingProfiles
{
    /**
     * Printify API client.
     *
     * @var PrintifyAPIClient
     */
    private $api_client;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Activity service.
     *
     * @var ActivityService
     */
    private $activity_service;

    /**
     * Constructor.
     *
     * @param PrintifyAPIClient $api_client      Printify API client.
     * @param Logger            $logger          Logger instance.
     * @param ActivityService   $activity_service Activity service.
     */
    public function __construct(
        PrintifyAPIClient $api_client, 
        Logger $logger, 
        ActivityService $activity_service
    ) {
        $this->api_client = $api_client;
        $this->logger = $logger;
        $this->activity_service = $activity_service;
    }

    /**
     * Initialize the shipping profiles.
     *
     * @return void
     */
    public function init()
    {
        // No additional initialization needed at this time
    }

    /**
     * Get all shipping profiles.
     *
     * @return array|WP_Error Shipping profiles or error.
     */
    public function getProfiles()
    {
        $this->logger->info('Fetching shipping profiles from Printify');

        $response = $this->api_client->getShippingProfiles();
        
        if (is_wp_error($response)) {
            $this->logger->error('Error fetching shipping profiles: ' . $response->get_error_message());
            return $response;
        }

        return $response;
    }

    /**
     * Get shipping profile by ID.
     *
     * @param int $profile_id Profile ID.
     * @return array|WP_Error Shipping profile or error.
     */
    public function getProfile($profile_id)
    {
        $this->logger->info("Fetching shipping profile {$profile_id} from Printify");

        $response = $this->api_client->getShippingProfile($profile_id);
        
        if (is_wp_error($response)) {
            $this->logger->error("Error fetching shipping profile {$profile_id}: " . $response->get_error_message());
            return $response;
        }

        return $response;
    }

    /**
     * Create shipping profile.
     *
     * @param array $profile_data Profile data.
     * @return array|WP_Error Result or error.
     */
    public function createProfile($profile_data)
    {
        $this->logger->info('Creating new shipping profile in Printify');

        $response = $this->api_client->createShippingProfile($profile_data);
        
        if (is_wp_error($response)) {
            $this->logger->error('Error creating shipping profile: ' . $response->get_error_message());
            return $response;
        }

        $this->activity_service->log('shipping_profile', sprintf(
            __('Created shipping profile "%s"', 'wp-woocommerce-printify-sync'),
            $profile_data['name'] ?? 'New Profile'
        ), [
            'profile_id' => $response['id'] ?? null,
            'profile_name' => $profile_data['name'] ?? 'New Profile',
            'time' => current_time('mysql')
        ]);

        return $response;
    }

    /**
     * Update shipping profile.
     *
     * @param int   $profile_id   Profile ID.
     * @param array $profile_data Profile data.
     * @return array|WP_Error Result or error.
     */
    public function updateProfile($profile_id, $profile_data)
    {
        $this->logger->info("Updating shipping profile {$profile_id} in Printify");

        $response = $this->api_client->updateShippingProfile($profile_id, $profile_data);
        
        if (is_wp_error($response)) {
            $this->logger->error("Error updating shipping profile {$profile_id}: " . $response->get_error_message());
            return $response;
        }

        $this->activity_service->log('shipping_profile', sprintf(
            __('Updated shipping profile "%s"', 'wp-woocommerce-printify-sync'),
            $profile_data['name'] ?? 'Profile ' . $profile_id
        ), [
            'profile_id' => $profile_id,
            'profile_name' => $profile_data['name'] ?? 'Profile ' . $profile_id,
            'time' => current_time('mysql')
        ]);

        return $response;
    }

    /**
     * Delete shipping profile.
     *
     * @param int $profile_id Profile ID.
     * @return array|WP_Error Result or error.
     */
    public function deleteProfile($profile_id)
    {
        $this->logger->info("Deleting shipping profile {$profile_id} from Printify");

        // Get profile data first to log the name
        $profile = $this->getProfile($profile_id);
        $profile_name = is_wp_error($profile) ? 'Profile ' . $profile_id : ($profile['name'] ?? 'Profile ' . $profile_id);

        $response = $this->api_client->deleteShippingProfile($profile_id);
        
        if (is_wp_error($response)) {
            $this->logger->error("Error deleting shipping profile {$profile_id}: " . $response->get_error_message());
            return $response;
        }

        $this->activity_service->log('shipping_profile', sprintf(
            __('Deleted shipping profile "%s"', 'wp-woocommerce-printify-sync'),
            $profile_name
        ), [
            'profile_id' => $profile_id,
            'profile_name' => $profile_name,
            'time' => current_time('mysql')
        ]);

        return $response;
    }
}
