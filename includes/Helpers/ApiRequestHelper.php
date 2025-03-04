<?phpnamespace ApolloWeb\WPWooCommercePrintifySync\Helpers;class ApiRequestHelper
{
    public static function getRequest($url, $headers)
    {
        $response = wp_remote_get($url, [
            'headers' => $headers,
        ]);        if (is_wp_error($response)) {
            return $response;
        }        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }    public static function postRequest($url, $headers, $body)
    {
        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => json_encode($body),
        ]);        if (is_wp_error($response)) {
            return $response;
        }        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }    public static function putRequest($url, $headers, $body)
    {
        $response = wp_remote_request($url, [
            'method'  => 'PUT',
            'headers' => $headers,
            'body'    => json_encode($body),
        ]);        if (is_wp_error($response)) {
            return $response;
        }        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }    public static function deleteRequest($url, $headers)
    {
        $response = wp_remote_request($url, [
            'method'  => 'DELETE',
            'headers' => $headers,
        ]);        if (is_wp_error($response)) {
            return $response;
        }        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
} Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: } Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------
#
#
# Commit Hash 16c804f
#
