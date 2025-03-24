<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core\Repository;

abstract class AbstractRepository {
    protected $db;
    protected $logger;
    protected $cache;
    
    public function __construct(\wpdb $db, LoggerInterface $logger, CacheInterface $cache) {
        $this->db = $db;
        $this->logger = $logger;
        $this->cache = $cache;
    }
    
    abstract protected function getTable(): string;
    abstract protected function getPrimaryKey(): string;
    
    public function find(int $id) {
        $cacheKey = $this->getCacheKey($id);
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        $result = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->getTable()} WHERE {$this->getPrimaryKey()} = %d",
                $id
            )
        );
        
        if ($result) {
            $this->cache->set($cacheKey, $result);
        }
        
        return $result;
    }

    protected function getCacheKey(int $id): string {
        return sprintf('%s_%s_%d', static::class, $this->getTable(), $id); 
    }
}
