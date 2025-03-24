# WP WooCommerce Printify Sync - Technical Implementation

## Architecture Overview

The plugin follows SOLID principles and PSR-12 standards with a focus on OOP. The architecture is organized as follows:

### Core Components

1. **Service Container** - Provides dependency injection and service management
2. **API Service** - Handles communication with Printify API
3. **Product Manager** - Manages product import, update, and synchronization
4. **Order Manager** - Handles order creation and status updates
5. **Shipping Manager** - Manages shipping profiles and zone configuration
6. **Ticketing Service** - AI-powered customer support system
7. **Email Queue Service** - SMTP-based email queue
8. **Action Scheduler Service** - Background processing for long-running tasks
9. **Template Service** - Template rendering system
10. **Logger Service** - Centralized logging system

### Directory Structure

```
wp-woocommerce-printify-sync/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── languages/
├── src/
│   ├── Admin/
│   │   ├── Pages/
│   │   └── Widgets/
│   ├── Api/
│   ├── Products/
│   ├── Orders/
│   ├── Shipping/
│   ├── Services/
│   ├── Webhooks/
│   └── Autoloader.php
├── templates/
├── vendor/
├── wp-woocommerce-printify-sync.php
└── uninstall.php
```

## Core Features Implementation

### 1. Settings Page

- **Encryption**: API keys and sensitive data stored encrypted using WordPress's encryption functions
- **API Testing**: AJAX-based API testing with connection verification
- **Shop Selection**: AJAX-driven shop listing and selection
- **OpenAI Integration**: Configuration for AI-powered ticketing system
- **Security**: Nonce verification and capability checks for all admin actions

### 2. Product Import & Update

- **Data Mapping**: Complete field mapping from Printify to WooCommerce
- **Variable Products**: Support for all product types and attributes
- **Background Processing**: Action Scheduler for large imports
- **Custom Meta**: Tracking of Printify product IDs and related data
- **Image Handling**: Media library integration via `media_sideload_image()`

### 3. Order Processing

- **Line Items**: Complete mapping of line items, shipping, and taxes
- **Currency Handling**: Exchange rate lock-in for consistent pricing
- **Tracking**: Automatic tracking information updates
- **Metadata**: Order meta for Printify order details

### 4. Order Status Management

- **Bi-directional Sync**: Status updates in both directions
- **Status Mapping**: Complete mapping of Printify statuses to WooCommerce
- **Notifications**: Admin notifications for status changes

### 5. Refund & Reprint Workflow

- **Customer Evidence**: Support for photo uploads and evidence collection
- **Approval Process**: Complete workflow from request to resolution
- **Documentation**: Order notes for each step in the process

### 6. Shipping Configuration

- **Profile Caching**: Efficient access to shipping profiles
- **Zone Creation**: Automatic WooCommerce shipping zone setup
- **Multi-provider Handling**: Support for orders with multiple providers
- **Geographic Pricing**: Location-based rate display

### 7. AI Ticketing System

- **Email Ingestion**: POP3-based email fetching
- **AI Analysis**: GPT-3 integration for ticket categorization and response generation
- **Threading**: Support for conversation threads
- **Attachments**: Secure storage of customer-provided evidence
- **Response Generation**: AI-assisted response suggestions

### 8. Email Queue

- **Batch Processing**: Efficient processing of email batches
- **Retry Logic**: Handling of failed sends
- **Status Tracking**: Queue monitoring and reporting

### 9. Dashboard & Reporting

- **Charts**: Visual representation of key metrics using Chart.js
- **Queue Monitoring**: Status of import, sync, and email queues
- **Recent Activity**: Latest tickets, orders, and sync operations

### 10. Logging & Debug

- **Centralized Logging**: Uniform logging system with filtering
- **Log Levels**: Support for debug, info, warning, and error levels
- **Retention**: Configurable log retention periods

## Frontend Assets

- **Bootstrap 5**: For admin UI components
- **Font Awesome**: Icon library
- **Chart.js**: Data visualization
- **Custom CSS/JS**: Per-page loading of assets

## Security Considerations

- **Data Validation**: Input sanitization and validation
- **Capability Checks**: Admin-level permission requirements
- **Nonce Verification**: CSRF protection for all AJAX requests
- **Secure Storage**: Encryption of sensitive configuration data

## Performance Optimization

- **Caching**: Strategic caching of API responses
- **Batching**: Batch processing for large operations
- **Background Processing**: Action Scheduler for resource-intensive tasks
- **Rate Limiting**: API request throttling with backoff mechanism
