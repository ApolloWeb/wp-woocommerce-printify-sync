#!/bin/bash

PLUGIN_DIR="."

# Step 1: Create Directories
mkdir -p \
$PLUGIN_DIR/assets/css \
$PLUGIN_DIR/assets/js \
$PLUGIN_DIR/assets/core/css \
$PLUGIN_DIR/assets/core/js \
$PLUGIN_DIR/assets/core/webfonts \
$PLUGIN_DIR/lib/BladeOne \
$PLUGIN_DIR/lib/GuzzleHttp \
$PLUGIN_DIR/lib/phpseclib \
$PLUGIN_DIR/logs \
$PLUGIN_DIR/languages \
$PLUGIN_DIR/src/Core \
$PLUGIN_DIR/src/Helpers \
$PLUGIN_DIR/src/Services \
$PLUGIN_DIR/src/Admin/Pages \
$PLUGIN_DIR/src/Webhooks \
$PLUGIN_DIR/src/Orders \
$PLUGIN_DIR/src/Providers \
$PLUGIN_DIR/templates/cache \
$PLUGIN_DIR/templates/partials/dashboard

# Step 2: Create Placeholder CSS & JS
touch $PLUGIN_DIR/assets/css/wpwps-{dashboard,settings,products,orders,shipping,tickets}.css
touch $PLUGIN_DIR/assets/js/wpwps-{dashboard,settings,products,orders,shipping,tickets}.js

# Step 3: Blade Templates
touch $PLUGIN_DIR/templates/wpwps-{dashboard,settings,products,orders,shipping,tickets}.blade.php
touch $PLUGIN_DIR/templates/layout.blade.php
touch $PLUGIN_DIR/templates/partials/dashboard/{header,footer}.blade.php

# Step 4: Download Frontend Assets
curl -L https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css -o $PLUGIN_DIR/assets/core/css/bootstrap.min.css
curl -L https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js -o $PLUGIN_DIR/assets/core/js/bootstrap.bundle.min.js
curl -L https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css -o $PLUGIN_DIR/assets/core/css/fontawesome.min.css
curl -L https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/js/all.min.js -o $PLUGIN_DIR/assets/core/js/fontawesome.min.js
curl -L https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/webfonts/fa-solid-900.woff2 -o $PLUGIN_DIR/assets/core/webfonts/fa-solid-900.woff2
curl -L https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js -o $PLUGIN_DIR/assets/core/js/chart.min.js

# Step 5: .gitignore
cat > $PLUGIN_DIR/.gitignore << 'EOF'
vendor/
lib/
logs/
templates/cache/
languages/*.mo
.DS_Store
*.log
EOF

# Step 6: Create .pot translation template
touch $PLUGIN_DIR/languages/wp-woocommerce-printify-sync.pot

# Step 7: Install BladeOne (core + helpers)
curl -L https://raw.githubusercontent.com/EFTEC/BladeOne/master/lib/BladeOne.php -o $PLUGIN_DIR/lib/BladeOne/BladeOne.php
curl -L https://raw.githubusercontent.com/EFTEC/BladeOne/master/lib/BladeOneHtml.php -o $PLUGIN_DIR/lib/BladeOne/BladeOneHtml.php
curl -L https://raw.githubusercontent.com/EFTEC/BladeOne/master/lib/BladeOneCache.php -o $PLUGIN_DIR/lib/BladeOne/BladeOneCache.php
curl -L https://raw.githubusercontent.com/EFTEC/BladeOne/master/lib/BladeOneLang.php -o $PLUGIN_DIR/lib/BladeOne/BladeOneLang.php
curl -L https://raw.githubusercontent.com/EFTEC/BladeOne/master/lib/BladeOneException.php -o $PLUGIN_DIR/lib/BladeOne/BladeOneException.php

# Step 8: Install GuzzleHttp
git clone --depth=1 https://github.com/guzzle/guzzle.git $PLUGIN_DIR/lib/guzzle-temp \
  && mv $PLUGIN_DIR/lib/guzzle-temp/src/* $PLUGIN_DIR/lib/GuzzleHttp/ \
  && rm -rf $PLUGIN_DIR/lib/guzzle-temp

# Step 9: Install phpseclib
# Replace the current phpseclib installation with a proper phpseclib3 installation
rm -rf $PLUGIN_DIR/lib/phpseclib
mkdir -p $PLUGIN_DIR/lib/phpseclib
git clone --depth=1 --branch=3.0 https://github.com/phpseclib/phpseclib.git $PLUGIN_DIR/lib/phpseclib-temp \
  && cp -r $PLUGIN_DIR/lib/phpseclib-temp/* $PLUGIN_DIR/lib/phpseclib/ \
  && rm -rf $PLUGIN_DIR/lib/phpseclib-temp

echo "✅ WP WooCommerce Printify Sync fully set up with BladeOne, GuzzleHttp, and phpseclib v3!"
