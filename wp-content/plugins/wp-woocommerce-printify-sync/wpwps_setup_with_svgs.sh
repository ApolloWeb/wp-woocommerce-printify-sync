PLUGIN_DIR="." && \
# Create directories
mkdir -p $PLUGIN_DIR/{assets/{css,js,images,core/{css,js,webfonts}},src/{Core,Helpers,Services,Admin/Pages,Webhooks,Orders,Providers},lib/{BladeOne,GuzzleHttp,phpseclib},templates/{partials/dashboard,cache},logs,languages} && \
# Create placeholder assets
touch $PLUGIN_DIR/assets/css/wpwps-{dashboard,settings,products,orders,shipping,tickets}.css && \
touch $PLUGIN_DIR/assets/js/wpwps-{dashboard,settings,products,orders,shipping,tickets}.js && \
# Blade views
touch $PLUGIN_DIR/templates/{wpwps-dashboard,wpwps-settings,wpwps-products,wpwps-orders,wpwps-shippng,wpwps-tickets}.blade.php && \
# Download core frontend assets
curl -L https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css -o $PLUGIN_DIR/assets/core/css/bootstrap.min.css && \
curl -L https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js -o $PLUGIN_DIR/assets/core/js/bootstrap.bundle.min.js && \
curl -L https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css -o $PLUGIN_DIR/assets/core/css/fontawesome.min.css && \
curl -L https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/webfonts/fa-solid-900.woff2 -o $PLUGIN_DIR/assets/core/webfonts/fa-solid-900.woff2 && \
curl -L https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js -o $PLUGIN_DIR/assets/core/js/chart.min.js && \
# Download SVG social icons
curl -L https://raw.githubusercontent.com/simple-icons/simple-icons/develop/icons/facebook.svg -o $PLUGIN_DIR/assets/images/facebook.svg && \
curl -L https://raw.githubusercontent.com/simple-icons/simple-icons/develop/icons/instagram.svg -o $PLUGIN_DIR/assets/images/instagram.svg && \
curl -L https://raw.githubusercontent.com/simple-icons/simple-icons/develop/icons/tiktok.svg -o $PLUGIN_DIR/assets/images/tiktok.svg && \
curl -L https://raw.githubusercontent.com/simple-icons/simple-icons/develop/icons/youtube.svg -o $PLUGIN_DIR/assets/images/youtube.svg
