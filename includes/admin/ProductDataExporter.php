<?php
/**
 * Product Data Exporter
 *
 * Specialized tool for exporting comprehensive product data from Printify
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\API\Printify\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ProductDataExporter {
    /**
     * API client
     * 
     * @var PrintifyApiClient
     */
    private $api_client;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_client = PrintifyApiClient::get_instance();
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwprintifysync_export_products', array($this, 'export_products_data'));
    }
    
    /**
     * Export products data with all associated information
     */
    public function export_products_data() {
        check_ajax_referer('wpwprintifysync-postman-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to export data.', 'wp-woocommerce-printify-sync')));
        }
        
        // Get export parameters
        $limit = isset($_REQUEST['limit']) ? absint($_REQUEST['limit']) : 20;
        $format = isset($_REQUEST['format']) ? sanitize_text_field($_REQUEST['format']) : 'json';
        $include_providers = isset($_REQUEST['include_providers']) && $_REQUEST['include_providers'] === 'true';
        $include_blueprints = isset($_REQUEST['include_blueprints']) && $_REQUEST['include_blueprints'] === 'true';
        $include_variants = isset($_REQUEST['include_variants']) && $_REQUEST['include_variants'] === 'true';
        $include_images = isset($_REQUEST['include_images']) && $_REQUEST['include_images'] === 'true';
        
        try {
            // Get shop ID from settings
            $settings = get_option('wpwprintifysync_settings', array());
            $shop_id = isset($settings['shop_id']) ? $settings['shop_id'] : '';
            
            if (empty($shop_id)) {
                throw new \Exception(__('Shop ID not configured in settings.', 'wp-woocommerce-printify-sync'));
            }
            
            // Step 1: Fetch products
            Logger::get_instance()->info('Starting product export', array(
                'limit' => $limit,
                'shop_id' => $shop_id,
                'user' => get_current_user_id()
            ));
            
            $products = $this->api_client->get("/shops/{$shop_id}/products.json", array(
                'limit' => $limit,
                'page' => 1
            ));
            
            if (empty($products) || !is_array($products)) {
                throw new \Exception(__('No products found or invalid API response.', 'wp-woocommerce-printify-sync'));
            }
            
            Logger::get_instance()->info('Retrieved products from API', array(
                'count' => count($products)
            ));
            
            // Step 2: Enrich product data
            $enriched_products = array();
            $provider_cache = array();
            $blueprint_cache = array();
            
            foreach ($products as $product) {
                $product_id = $product['id'];
                
                // Get detailed product information
                $detailed_product = $this->api_client->get("/shops/{$shop_id}/products/{$product_id}.json");
                
                // Create enriched product record
                $enriched_product = array(
                    'id' => $product_id,
                    'title' => $detailed_product['title'],
                    'description' => $detailed_product['description'],
                    'tags' => $detailed_product['tags'],
                    'options' => $detailed_product['options'],
                    'created_at' => $detailed_product['created_at'],
                    'updated_at' => $detailed_product['updated_at'],
                    'visible' => $detailed_product['visible'],
                    'blueprint_id' => $detailed_product['blueprint_id'],
                    'print_provider_id' => $detailed_product['print_provider_id'],
                    'user_id' => $detailed_product['user_id'],
                    'shop_id' => $detailed_product['shop_id']
                );
                
                // Include variants if requested
                if ($include_variants && isset($detailed_product['variants'])) {
                    $enriched_product['variants'] = $detailed_product['variants'];
                }
                
                // Include images if requested
                if ($include_images && isset($detailed_product['images'])) {
                    $enriched_product['images'] = $detailed_product['images'];
                }
                
                // Get print provider information if requested
                if ($include_providers && !empty($detailed_product['print_provider_id'])) {
                    $provider_id = $detailed_product['print_provider_id'];
                    
                    if (!isset($provider_cache[$provider_id])) {
                        // Get provider details and cache them
                        $provider_data = $this->api_client->get("/print-providers/{$provider_id}.json");
                        $provider_cache[$provider_id] = $provider_data;
                    }
                    
                    $enriched_product['print_provider'] = $provider_cache[$provider_id];
                }
                
                // Get blueprint information if requested
                if ($include_blueprints && !empty($detailed_product['blueprint_id'])) {
                    $blueprint_id = $detailed_product['blueprint_id'];
                    
                    if (!isset($blueprint_cache[$blueprint_id])) {
                        // Get blueprint details and cache them
                        $blueprint_data = $this->api_client->get("/catalog/blueprints/{$blueprint_id}.json");
                        $blueprint_cache[$blueprint_id] = $blueprint_data;
                    }
                    
                    $enriched_product['blueprint'] = $blueprint_cache[$blueprint_id];
                }
                
                $enriched_products[] = $enriched_product;
                
                // Log progress for large exports
                if (count($enriched_products) % 5 == 0) {
                    Logger::get_instance()->debug('Export progress', array(
                        'processed' => count($enriched_products),
                        'total' => count($products)
                    ));
                }
            }
            
            Logger::get_instance()->info('Completed data enrichment', array(
                'product_count' => count($enriched_products),
                'providers_cached' => count($provider_cache),
                'blueprints_cached' => count($blueprint_cache)
            ));
            
            // Step 3: Format and return the data
            $export_data = $this->format_export_data($enriched_products, $format);
            $download_url = $this->generate_export_file($export_data, $format, 'products');
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Successfully exported %d products with associated data.', 'wp-woocommerce-printify-sync'), 
                    count($enriched_products)
                ),
                'product_count' => count($enriched_products),
                'provider_count' => count($provider_cache),
                'blueprint_count' => count($blueprint_cache),
                'download_url' => $download_url
            ));
            
        } catch (\Exception $e) {
            Logger::get_instance()->error('Product export failed', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            wp_send_json_error(array(
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ));
        }
    }
    
    /**
     * Format export data based on requested format
     *
     * @param array $data Product data
     * @param string $format Export format
     * @return string Formatted data
     */
    private function format_export_data($data, $format) {
        switch ($format) {
            case 'csv':
                return $this->format_as_csv($data);
                
            case 'xml':
                return $this->format_as_xml($data);
                
            case 'excel':
                return $this->format_as_excel($data);
                
            case 'json':
            default:
                return json_encode(
                    $data, 
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
        }
    }
    
    /**
     * Generate export file and return download URL
     *
     * @param string $data Export data
     * @param string $format Export format
     * @param string $type Export type
     * @return string Download URL
     */
    private function generate_export_file($data, $format, $type) {
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/printify-exports';
        
        // Create directory if it doesn't exist
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
            file_put_contents($export_dir . '/index.php', '<?php // Silence is golden');
        }
        
        // Generate filename
        $timestamp = date('YmdHis');
        $filename = "printify-{$type}-export-{$timestamp}.{$format}";
        $filepath = $export_dir . '/' . $filename;
        
        // Write data to file
        file_put_contents($filepath, $data);
        
        // Generate secure download token
        $token = wp_generate_password(32, false);
        set_transient('printify_export_' . $token, $filepath, 3600); // 1 hour expiry
        
        // Return download URL
        return add_query_arg(
            array(
                'action' => 'wpwprintifysync_download_export',
                'token' => $token,
                'nonce' => wp_create_nonce('printify_export_download')
            ),
            admin_url('admin-ajax.php')
        );
    }
    
    /**
     * Format data as CSV
     *
     * @param array $data Product data
     * @return string CSV content
     */
    private function format_as_csv($data) {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Flatten the data structure for CSV
        $flattened_data = array();
        foreach ($data as $product) {
            $flat_product = $this->flatten_product_for_csv($product);
            $flattened_data[] = $flat_product;
        }
        
        // Get all possible headers from flattened data
        $headers = array();
        foreach ($flattened_data as $item) {
            foreach (array_keys($item) as $key) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
        }
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write rows
        foreach ($flattened_data as $row) {
            $csv_row = array();
            foreach ($headers as $header) {
                $csv_row[] = isset($row[$header]) ? $row[$header] : '';
            }
            fputcsv($output, $csv_row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Flatten product data for CSV export
     *
     * @param array $product Product data
     * @return array Flattened product data
     */
    private function flatten_product_for_csv($product) {
        $flat = array(
            'id' => $product['id'],
            'title' => $product['title'],
            'description' => $product['description'],
            'tags' => is_array($product['tags']) ? implode(', ', $product['tags']) : $product['tags'],
            'created_at' => $product['created_at'],
            'updated_at' => $product['updated_at'],
            'visible' => $product['visible'] ? 'Yes' : 'No',
            'blueprint_id' => $product['blueprint_id'],
            'print_provider_id' => $product['print_provider_id']
        );
        
        // Add provider data if available
        if (isset($product['print_provider'])) {
            $flat['provider_name'] = $product['print_provider']['title'];
            $flat['provider_location'] = $product['print_provider']['location'];
        }
        
        // Add blueprint data if available
        if (isset($product['blueprint'])) {
            $flat['blueprint_title'] = $product['blueprint']['title'];
            $flat['blueprint_description'] = $product['blueprint']['description'];
        }
        
        // Add variant summary
        if (isset($product['variants']) && is_array($product['variants'])) {
            $flat['variant_count'] = count($product['variants']);
            
            // Add price range
            $prices = array_column($product['variants'], 'price');
            $flat['min_price'] = !empty($prices) ? min($prices) : 0;
            $flat['max_price'] = !empty($prices) ? max($prices) : 0;
            
            // Add SKUs as comma-separated list
            $skus = array_column($product['variants'], 'sku');
            $flat['skus'] = implode(', ', array_filter($skus));
        }
        
        return $flat;
    }

    /**
     * Format data as XML
     *
     * @param array $data Product data
     * @return string XML content
     */
    private function format_as_xml($data) {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><products></products>');
        
        foreach ($data as $product) {
            $xml_product = $xml->addChild('product');
            $this->array_to_xml($product, $xml_product);
        }
        
        return $xml->asXML();
    }
    
    /**
     * Format data as Excel spreadsheet
     *
     * @param array $data Product data
     * @return string Excel file content
     */
    private function format_as_excel($data) {
        // Would typically use PhpSpreadsheet library here
        // For simplicity, we'll return CSV data which Excel can open
        return $this->format_as_csv($data);
    }
    
    /**
     * Helper function to convert array to XML
     *
     * @param array $data Array data
     * @param \SimpleXMLElement $xml_data XML element
     */
    private function array_to_xml($data, &$xml_data) {
        foreach ($data as $