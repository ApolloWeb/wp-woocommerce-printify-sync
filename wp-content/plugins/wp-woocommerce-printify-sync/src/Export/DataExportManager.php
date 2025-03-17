<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Export;

class DataExportManager
{
    private const EXPORT_FORMATS = ['csv', 'json', 'xml', 'xlsx'];
    private const BATCH_SIZE = 1000;

    public function exportData(string $type, array $filters = [], string $format = 'csv'): string
    {
        if (!in_array($format, self::EXPORT_FORMATS)) {
            throw new \InvalidArgumentException('Invalid export format');
        }

        $data = $this->getData($type, $filters);
        return $this->formatData($data, $format);
    }

    private function getData(string $type, array $filters): array
    {
        global $wpdb;

        switch ($type) {
            case 'sync_history':
                return $this->getSyncHistory($wpdb, $filters);
            case 'order_fulfillment':
                return $this->getOrderFulfillment($wpdb, $filters);
            case 'api_logs':
                return $this->getApiLogs($wpdb, $filters);
            case 'error_logs':
                return $this->getErrorLogs($wpdb, $filters);
            default:
                throw new \InvalidArgumentException('Invalid export type');
        }
    }

    private function getSyncHistory($wpdb, array $filters): array
    {
        $query = "
            SELECT 
                ps.product_id,
                p.post_title as product_name,
                ps.printify_id,
                ps.sync_status,
                ps.sync_message,
                ps.last_sync,
                ps.created_at
            FROM {$wpdb->prefix}wpwps_product_sync_status ps
            LEFT JOIN {$wpdb->posts} p ON ps.product_id = p.ID
            WHERE 1=1
        ";

        if (!empty($filters['date_from'])) {
            $query .= $wpdb->prepare(" AND ps.created_at >= %s", $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query .= $wpdb->prepare(" AND ps.created_at <= %s", $filters['date_to']);
        }

        if (!empty($filters['status'])) {
            $query .= $wpdb->prepare(" AND ps.sync_status = %s", $filters['status']);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    private function formatData(array $data, string $format): string
    {
        switch ($format) {
            case 'csv':
                return $this->formatCsv($data);
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            case 'xml':
                return $this->formatXml($data);
            case 'xlsx':
                return $this->formatXlsx($data);
            default:
                throw new \InvalidArgumentException('Invalid format');
        }
    }

    private function formatCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($output, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    private function formatXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><data></data>');
        
        foreach ($data as $item) {
            $node = $xml->addChild('item');
            foreach ($item as $key => $value) {
                $node->addChild($key, htmlspecialchars((string)$value));
            }
        }
        
        return $xml->asXML();
    }

    private function formatXlsx(array $data): string
    {
        require_once WPWPS_PLUGIN_DIR . 'vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Write headers
        $headers = array_keys($data[0]);
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
        }
        
        // Write data
        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 2, $value);
            }
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'wpwps_export_');
        $writer->save($tempFile);
        
        return $tempFile;
    }
}