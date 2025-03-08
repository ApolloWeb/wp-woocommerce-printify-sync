# Developer Documentation

## Introduction
This document provides detailed information for developers working on the WordPress WooCommerce Printify Sync Plugin.

## Directory Structure
- `assets/`: Contains CSS, images, and JavaScript files.
- `includes/`: Contains core classes, helpers, and controllers.
  - `Abstracts/`: Contains abstract base classes.
  - `Helpers/`: Contains helper classes.
  - `Services/`: Contains service classes for business logic.
  - `Controllers/`: Contains controller classes.
- `languages/`: Contains translation files.
- `templates/`: Contains Blade templates.
- `tests/`: Contains unit and integration tests.
- `vendor/`: Contains Composer dependencies.

## Core Classes
### Abstracts
- `BaseController`: Abstract base class for controllers.

### Helpers
- `Logger`: Helper class for logging messages.
- `Settings`: Helper class for managing plugin settings.

### Services
- `ApiClient`: Service class for handling API requests to Printify.
- `ProductSyncService`: Service class for syncing products.
- `OrderSyncService`: Service class for syncing orders.

### Controllers
- `AdminController`: Controller for admin functionality.
- `ApiController`: Controller for API functionality.
- `FrontendController`: Controller for frontend functionality.
- `PrintifySyncController`: Controller for syncing products and orders with Printify.

## Blade Templating
The plugin uses Laravel Blade for templating. Blade templates are located in the `templates/` directory.

## Settings
The plugin settings can be configured from the WordPress admin dashboard under "Settings" -> "Printify Sync".

## Tests
Unit and integration tests are located in the `tests/` directory. The plugin uses PHPUnit for testing.