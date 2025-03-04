// Find all stylesheets on the page
const allStyles = document.querySelectorAll('link[rel="stylesheet"]');

console.log('=== All Stylesheets ===');
allStyles.forEach(style => {
    const isCore = style.href.includes('/wp-admin/') || style.href.includes('/wp-includes/');
    const isPlugin = style.href.includes('wp-woocommerce-printify-sync');
    
    console.log(
        isPlugin ? '🔵 PLUGIN: ' : (isCore ? '⚪ CORE: ' : '⚫ OTHER: '),
        style.id || 'No ID',
        style.href
    );
});

// Count styles by source
const counts = {
    plugin: 0,
    core: 0,
    other: 0
};

allStyles.forEach(style => {
    if (style.href.includes('wp-woocommerce-printify-sync')) {
        counts.plugin++;
    } else if (style.href.includes('/wp-admin/') || style.href.includes('/wp-includes/')) {
        counts.core++;
    } else {
        counts.other++;
    }
});

console.log('\n=== Style Count ===');
console.log('Plugin Styles:', counts.plugin);
console.log('WordPress Core Styles:', counts.core);
console.log('Other Styles:', counts.other);