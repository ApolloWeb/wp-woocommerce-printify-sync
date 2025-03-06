<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Models;

class OrderModel {
    private $id;
    private $status;
    private $total;

    public function __construct($id, $status, $total) {
        $this->id = $id;
        $this->status = $status;
        $this->total = $total;
    }

    public function getId() {
        return $this->id;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getTotal() {
        return $this->total;
    }
}