/**
 * JavaScript file: update-readme.js for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Last Update On: 2025-02-28 at 03:02:39
 */
const fs = require('fs');
const path = require('path');

const readmePath = path.join(__dirname, 'README.md');
const includesDir = path.join(__dirname, 'includes');
const adminIncludesDir = path.join(__dirname, 'admin/includes');

const getFilesRecursively = (dir) => {
  let results = [];
  const list = fs.readdirSync(dir);
  list.forEach((file) => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    if (stat && stat.isDirectory()) {
      results = results.concat(getFilesRecursively(filePath));
    } else {
      results.push(filePath);
    }
  });
  return results;
};

const generateFileStructure = (dir, baseDir) => {
  const files = getFilesRecursively(dir);
  return files.map((file) => file.replace(baseDir, '').replace(/\/g, '/')).join('
      │   ');
};

const includesStructure = generateFileStructure(includesDir, __dirname);
const adminIncludesStructure = generateFileStructure(adminIncludesDir, __dirname);

const readmeContent = `
# WP WooCommerce Printify Sync

## Overview
\`wp-woocommerce-printify-sync\` is a WordPress plugin that integrates WooCommerce with Printify, allowing seamless product synchronization.

## File Structure
The plugin's files are structured as follows:

\`\`\`
      │── wp-woocommerce-printify-sync.php
      │
      ├── admin/
      │   ├── Admin.php
      │   ├── assets/
      │   │   ├── js/
      │   │   │   ├── admin-script.js
      │   │   │   ├── products.js
      │   │   │   ├── shops.js
      │   │   ├── css/
      │   │   │   ├── admin-styles.css
      │   ├── templates/
      │   │   ├── products-section.php
      │   │   ├── settings-page.php
      │   │   ├── shops-section.php
      │   ├── includes/
      │   │   ├── Helper.php
      │
${adminIncludesStructure}
      ├── includes/
${includesStructure}
      ├── .github/
      │   ├── workflows/
      │   │   ├── php.yml
      │
      ├── .gitignore
      │
      ├── .php-cs-fixer.php
      │
      ├── .phpcs.xml
      │
      ├── .wp-env.json
      │
      ├── LICENSE
      │
      ├── README.md
      │
      ├── composer.json
      │
      ├── composer.lock
      │
      ├── eslint.config.js
      │
      ├── package-lock.json
      │
      ├── package.json
      │
      ├── phpcs.xml.dist
      │
      ├── phpstan.neon
      │
      ├── phpunit.xml.dist
\`\`\`

## File Descriptions
- **wp-woocommerce-printify-sync.php**: The main plugin file that initializes the plugin.
