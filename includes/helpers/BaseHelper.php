<?php
/**
 * Base Helper class for shared functionality
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

abstract class BaseHelper {
    /**
     * @var string Current timestamp
     */
    protected $timestamp = '2025-03-05 18:57:33';
    
    /**
     * @var string Current user
     */
    protected $user = 'ApolloWeb';
    
    /**
     * Get meta from any supported object using data store abstraction
     *
     * @param int $object_id Object ID (post, order, etc.)
     * @param string $meta_key Meta key
     * @param bool $single Whether to return a single value
     * @param string $object_type Object type (product, order)
     * @return mixed Meta value(s)
     */
    protected function getMeta($object_id, $meta_key, $single = true, $object_type = null) {
        if (!$object_type) {
            // Try to determine object type if not specified
            if (function_exists('wc_get_order') && wc_get_order($object_id)) {
                $object_type = 'order';
            } elseif (function_exists('wc_get_product') && wc_get_product($object_id)) {
                $object_type = 'product';
            }
        }
        
        if ($object_type === 'order') {
            $object = wc_get_order($object_id);
            return $object ? $object->get_meta($meta_key, $single) : null;
        } 
        
        if ($object_type === 'product') {
            $object = wc_get_product($object_id);
            return $object ? $object->get_meta($meta_key, $single) : null;
        }
        
        // Fall back to WordPress meta functions
        return get_metadata('post', $object_id, $meta_key, $single);
    }
    
    /**
     * Update meta for any supported object using data store abstraction
     *
     * @param int $object_id Object ID (post, order, etc.)
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     * @param string $object_type Object type (product, order)
     * @return bool Success
     */
    protected function updateMeta($object_id, $meta_key, $meta_value, $object_type = null) {
        if (!$object_type) {
            // Try to determine object type if not specified
            if (function_exists('wc_get_order') && wc_get_order($object_id)) {
                $object_type = 'order';
            } elseif (function_exists('wc_get_product') && wc_get_product($object_id)) {
                $object_type = 'product';
            }
        }
        
        if ($object_type === 'order') {
            $object = wc_get_order($object_id);
            if ($object) {
                $object->update_meta_data($meta_key, $meta_value);
                return $object->save() > 0;
            }
            return false;
        }
        
        if ($object_type === 'product') {
            $object = wc_get_product($object_id);
            if ($object) {
                $object->update_meta_data($meta_key, $meta_value);
                return $object->save() > 0;
            }
            return false;
        }
        
        // Fall back to WordPress meta functions
        return update_metadata('post', $object_id, $meta_key, $meta_value);
    }
    
    /**
     * Delete meta for any supported object using data store abstraction
     *
     * @param int $object_id Object ID (post, order, etc.)
     * @param string $meta_key Meta key
     * @param string $object_type Object type (product, order)
     * @return bool Success
     */
    protected function deleteMeta($object_id, $meta_key, $object_type = null) {
        if (!$object_type) {
            // Try to determine object type if not specified
            if (function_exists('wc_get_order') && wc_get_order($object_id)) {
                $object_type = 'order';
            } elseif (function_exists('wc_get_product') && wc_get_product($object_id)) {
                $object_type = 'product';
            }
        }
        
        if ($object_type === 'order') {
            $object = wc_get_order($object_id);
            if ($object) {
                $object->delete_meta_data($meta_key);
                return $object->save() > 0;
            }
            return false;
        }
        
        if ($object_type === 'product') {
            $object = wc_get_product($object_id);
            if ($object) {
                $object->delete_meta_data($meta_key);
                return $object->save() > 0;
            }
            return false;
        }
        
        // Fall back to WordPress meta functions
        return delete_metadata('post', $object_id, $meta_key);
    }
    
    /**
     * Add standardized audit trail metadata
     *
     * @param int $object_id Object ID
     * @param string $action Action being performed
     * @param string $object_type Object type
     * @return bool Success
     */
    protected function addAuditTrail($object_id, $action, $object_type = null) {
        $this->updateMeta($object_id, "_printify_{$action}_at", $this->timestamp, $object_type);
        $this->updateMeta($object_id, "_printify_{$action}_by", $this->user, $object_type);
        return true;
    }
}