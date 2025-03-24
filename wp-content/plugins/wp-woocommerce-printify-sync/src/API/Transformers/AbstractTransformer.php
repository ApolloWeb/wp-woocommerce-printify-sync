<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API\Transformers;

abstract class AbstractTransformer {
    protected array $relationships = [];
    
    abstract public function transform(array $data): array;
    
    public function withRelationships(array $relationships): self {
        $this->relationships = $relationships;
        return $this;
    }
    
    protected function transformCollection(array $data, callable $callback): array {
        return array_map($callback, $data);
    }
}
