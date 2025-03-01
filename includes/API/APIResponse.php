<?php
/**
 * API Response handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

/**
 * Class APIResponse
 */
class APIResponse {

    /**
     * Response data.
     *
     * @var mixed
     */
    private $data;

    /**
     * Response code.
     *
     * @var int
     */
    private $code;

    /**
     * Error message, if any.
     *
     * @var string
     */
    private $error;

    /**
     * Success flag.
     *
     * @var bool
     */
    private $success;

    /**
     * APIResponse constructor.
     *
     * @param mixed  $data    Response data.
     * @param int    $code    Response code.
     * @param string $error   Error message.
     * @param bool   $success Success flag.
     */
    public function __construct($data = null, $code = 200, $error = '', $success = true) {
        $this->data = $data;
        $this->code = $code;
        $this->error = $error;
        $this->success = $success;
    }

    /**
     * Check if the response is successful.
     *
     * @return bool
     */
    public function is_success() {
        return $this->success && ($this->code >= 200 && $this->code < 300);
    }

    /**
     * Get response data.
     *
     * @return mixed
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Get response code.
     *
     * @return int
     */
    public function get_code() {
        return $this->code;
    }

    /**
     * Get error message.
     *
     * @return string
     */
    public function get_error() {
        return $this->error;
    }

    /**
     * Convert response to array.
     *
     * @return array
     */
    public function to_array() {
        return array(
            'success' => $this->success,
            'code'    => $this->code,
            'data'    => $this->data,
            'error'   => $this->error,
            'time'    => current_time('mysql'),
            'user'    => 'ApolloWeb'
        );
    }
}