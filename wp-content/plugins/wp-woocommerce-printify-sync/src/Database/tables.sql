CREATE TABLE IF NOT EXISTS `{prefix}wpwps_import_batches` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `total_products` int(11) NOT NULL,
    `progress` float DEFAULT 0,
    `status` varchar(20) NOT NULL,
    `completed_chunks` int(11) DEFAULT 0,
    `failed_chunks` int(11) DEFAULT 0,
    `created_at` datetime NOT NULL,
    `created_by` varchar(60) NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `status` (`status`),
    KEY `created_at` (`created_at`)
) {charset_collate};

CREATE TABLE IF NOT EXISTS `{prefix}wpwps_import_chunks` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `batch_id` bigint(20) NOT NULL,
    `chunk_index` int(11) NOT NULL,
    `total_chunks` int(11) NOT NULL,
    `products` longtext NOT NULL,
    `status` varchar(20) NOT NULL,
    `error` text DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `created_by` varchar(60) NOT NULL,
    `completed_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `batch_id` (`batch_id`),
    KEY `status` (`status`)
) {charset_collate};