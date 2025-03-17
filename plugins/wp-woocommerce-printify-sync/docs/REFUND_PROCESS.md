# Printify Return, Refund & Reprint Process
Last Updated: 2025-03-16 19:02:57 UTC
Author: ApolloWeb

## Process Overview

### 1. Return Types
- **Quality Issues**: Product quality doesn't meet standards
- **Misprinted Items**: Wrong design/placement/colors
- **Sizing Issues**: Product size differs from description
- **Lost/Damaged**: Items damaged during shipping
- **Wrong Address**: Delivery to incorrect address

### 2. Printify Policy
- 30-day window for claims
- Photos required for quality issues
- Tracking required for shipping issues
- No return shipping for quality issues
- Customer keeps item in most cases

### 3. Action Flow

#### A. Quality Issues/Misprints
1. Customer reports issue
2. Photos collected
3. Submit to Printify
4. Choose reprint or refund
5. If approved:
   - Reprint: New order created
   - Refund: Money returned to store

#### B. Lost/Damaged
1. Verify tracking status
2. Submit claim to Printify
3. Wait for investigation (2-3 business days)
4. If approved:
   - Reprint shipped
   - Or refund processed

## Implementation Plan

### 1. Data Structure
```sql
CREATE TABLE `{$wpdb->prefix}wpwps_quality_claims` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `order_id` bigint(20) unsigned NOT NULL,
    `printify_order_id` varchar(50) NOT NULL,
    `claim_type` varchar(20) NOT NULL,
    `reason` text NOT NULL,
    `status` varchar(20) NOT NULL,
    `photos` text,
    `reprint_order_id` varchar(50),
    `refund_amount` decimal(10,2),
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `printify_order_id` (`printify_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;