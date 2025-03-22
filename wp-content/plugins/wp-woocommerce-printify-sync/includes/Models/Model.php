<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Models;

abstract class Model {
    protected $data = [];
    protected $fillable = [];

    public function __construct(array $data = []) {
        $this->fill($data);
    }

    public function fill(array $data): void {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->data[$key] = $value;
            }
        }
    }

    public function __get($name) {
        return $this->data[$name] ?? null;
    }
}
