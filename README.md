# WP WooCommerce Printify Sync

**Plugin Name:** WP WooCommerce Printify Sync  
**Description:** Sync products from Printify to WooCommerce  
**Plugin URI:** https://github.com/ApolloWeb/wp-woocommerce-printify-sync  
**Version:** 1.1.1  
**Author:** ApolloWeb  
**Author URI:** https://github.com/ApolloWeb  
**Text Domain:** wp-woocommerce-printify-sync  
**Domain Path:** /languages  
**Requires at least:** 5.6  
**Requires PHP:** 7.3  
**License:** MIT  

## Table of Contents

1. Plugin Meta & Introduction
2. System Objectives
3. Architecture & Design
4. Detailed Features
   - 4.1. API Integrations
   - 4.2. Shipping Profiles, Tiered Pricing & Zones
   - 4.3. Currency Exchange & Dynamic Pricing
   - 4.4. Product Synchronization and Field Mapping
   - 4.5. Image Handling
   - 4.6. Order Processing & Webhook Handling
   - 4.7. Ticketing System & Communication Integration
   - 4.8. Admin UI & Dashboard
   - 4.9. Performance, Caching & Logging
   - 4.10. Security, Credential Management & Metadata Extensions
   - 4.11. Testing, Debugging & Continuous Integration
   - 4.12. Refund & Dispute Management
5. Environment Handling & Deployment
6. Future Enhancements
7. Additional Considerations
8. Conclusion

## Introduction

WP WooCommerce Printify Sync is a plugin designed to sync products from Printify to WooCommerce. This plugin provides seamless integration between the two platforms, automating tasks such as product synchronization, order processing, and currency exchange.

## 4.8. Admin UI & Dashboard

### Admin Pages & Functionalities

#### Settings Page
- **API Key Management**
  - Securely stores and validates Printify, WooCommerce, Geolocation, and Currency APIs.
  - Uses OpenSSL encryption with salting and masking.
  - AJAX-powered save & test button for real-time validation.
  - Prevents CORS issues using `admin-ajax`.

- **Automation Controls**
  - Toggle automatic product sync.
  - Configure cron job intervals for exchange rate updates & stock syncing.
  - Enable/disable auto-creation of missing shipping zones.

- **Production/Development Toggle**
  - Includes a visual indicator in the admin UI.
  - Switches API endpoints dynamically based on mode.

#### Logs & Debugging
- **Complete log viewer** with full-text search & filtering.
- Logs are stored in the database for 14 days, then automatically exported to Google Drive/OneDrive.
- Error Categorization:
  - Webhook failures.
  - API rate limit errors.
  - Order processing issues.
  - Refund request failures.
  - Admin is alerted if failures exceed a configurable threshold.

#### Testing & Postman API Integration
- Supports managing Postman mock servers.
- Upload live test data (20 sample products) to Postman.
- Live API testing UI for executing API calls directly.
- API endpoint automatically switches based on production/dev mode.

#### Product & Image Handling
- **Image Optimization**
  - Uses SMUSH for lossless compression.
  - Future feature: Offload images to Cloudflare S2/AWS S3.

- **Product Import Process**
  - Runs only once on install, after which webhooks take over.
  - Implements action scheduler for background imports.
  - Chunking & queueing prevent API overuse.
  - Retry & backoff mechanisms for handling failures.

#### Shipping & WooCommerce Zone Mapping
- Fetches real-time Printify shipping profiles.
- Auto-creates missing WooCommerce shipping zones.
- Multi-provider orders require customers to select separate shipping methods.
- Table of estimated shipping costs & delivery times on product pages.

#### Ticketing System Enhancements
- **Intelligent email extraction**
  - Detects order numbers, inquiry types, and customer details.
  - Automatically links customer emails to existing tickets.

- **Automated responses**
  - Refund requests prompt the user for evidence & refund/reprint choice.
  - Denied refunds receive policy-based auto-reply.
- Admin can generate emails directly from tickets using WooCommerce email templates.

## 4.12. Refund & Reprint Handling

### Automated Refund Workflow
1. Customer initiates refund via email or WooCommerce order page.
2. System captures email and imports it into the ticketing system.
3. AI-based extraction identifies order number and refund type.
4. Automated response requests photographic evidence & refund/reprint preference.
5. Admin approves valid refunds, triggering Printify API call (`POST /orders/{order_id}/refunds`).
6. WooCommerce order status updates to reflect Printify’s response.

### Reprint Handling
- If Printify approves the reprint, the order follows normal fulfillment steps.
- Order retains the same order number.
- Order status updates to printify-reprint, and customer is notified.

## Conclusion

This document provides a detailed specification for integrating Printify with WooCommerce, covering API endpoints, order management, shipping, and an enhanced Bootstrap-based UI with WooCommerce Purple and Sassy SAAS styling. The ticketing system now includes POP3/SMTP mailbox polling, allowing automated ticket creation from incoming emails, direct replies using WooCommerce email templates, queued outgoing emails, email threading, and attachment support for a fully integrated customer support experience. Intelligent information extraction ensures emails are categorized properly, order details are auto-matched, and refund requests are processed efficiently.

Additionally, the Admin UI now includes a fully detailed Dashboard, Order Management, Refund Processing, Ticketing, Logging, API Testing, and Automated Currency/Stock Updates to enhance stability, automation, and scalability of the system.

Furthermore, unit testing with GitHub Actions, local workflow testing with `act`, API authentication details with example responses, and caching using transients ensure the system maintains high-quality, robust, and automated testing coverage.