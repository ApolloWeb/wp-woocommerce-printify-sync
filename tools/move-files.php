<?php
/**
 * File moving script
 * 
 * This script moves files from the source directory to the WordPress plugin directory
 */

// Define source and destination directories
$src_base = '/home/apolloweb/projects/wp-woocommerce-printify-sync';
$dest_base = '/home/apolloweb/projects/wp-woocommerce-printify-sync/wp-content/plugins/wp-woocommerce-printify-sync';

// Create an array of files to move
$files_to_move = [
    // JS files
    'assets/js/email-testing.js' => 'assets/js/email-testing.js',
    
    // CSS files
    'assets/css/email-settings.css' => 'assets/css/email-settings.css',
    
    // Admin files
    'src/Admin/EmailSettingsPage.php' => 'src/Admin/EmailSettingsPage.php',
    
    // Email files
    'src/Email/Database/EmailQueueTable.php' => 'src/Email/Database/EmailQueueTable.php',
    'src/Email/Services/EmailAnalyzer.php' => 'src/Email/Services/EmailAnalyzer.php',
    'src/Email/Services/EmailProcessor.php' => 'src/Email/Services/EmailProcessor.php',
    'src/Email/Services/EmailQueueMonitor.php' => 'src/Email/Services/EmailQueueMonitor.php',
    'src/Email/Services/EmailTemplateLoader.php' => 'src/Email/Services/EmailTemplateLoader.php',
    'src/Email/Services/EmailTemplateManager.php' => 'src/Email/Services/EmailTemplateManager.php',
    'src/Email/Services/EmailTestingService.php' => 'src/Email/Services/EmailTestingService.php',
    'src/Email/Services/POP3Service.php' => 'src/Email/Services/POP3Service.php',
    'src/Email/Services/QueueManager.php' => 'src/Email/Services/QueueManager.php',
    'src/Email/Services/SMTPService.php' => 'src/Email/Services/SMTPService.php',
    'src/Email/Services/TemplateValidator.php' => 'src/Email/Services/TemplateValidator.php',
    
    // Orders files
    'src/Orders/EmailRequestHandler.php' => 'src/Orders/EmailRequestHandler.php',
    'src/Orders/OrderAnalyzer.php' => 'src/Orders/OrderAnalyzer.php',
    'src/Orders/OrderStatuses.php' => 'src/Orders/OrderStatuses.php',
    'src/Orders/OrderSync.php' => 'src/Orders/OrderSync.php',
    'src/Orders/OrderTicketHandler.php' => 'src/Orders/OrderTicketHandler.php',
    'src/Orders/ReprintHandler.php' => 'src/Orders/ReprintHandler.php',
    
    // Services files
    'src/Services/AIResponseGenerator.php' => 'src/Services/AIResponseGenerator.php',
    'src/Services/IDMapper.php' => 'src/Services/IDMapper.php',
    'src/Services/RefundWorkflowHandler.php' => 'src/Services/RefundWorkflowHandler.php',
    
    // Utilities files
    'src/Utilities/Diagnostics.php' => 'src/Utilities/Diagnostics.php',
    
    // Core files
    'src/Autoloader.php' => 'src/Autoloader.php',
    'src/Plugin.php' => 'src/Plugin.php',
    
    // Templates
    'templates/admin/email-settings.php' => 'templates/admin/email-settings.php',
    'templates/admin/email-template-editor.php' => 'templates/admin/email-template-editor.php',
    'templates/admin/email-test-panel.php' => 'templates/admin/email-test-panel.php',
    'templates/emails/ticket-response.php' => 'templates/emails/ticket-response.php',
];

// Create necessary directories
$directories = [
    'assets/js',
    'assets/css',
    'src/Admin',
    'src/Email/Database',
    'src/Email/Services',
    'src/Orders',
    'src/Services',
    'src/Utilities',
    'templates/admin',
    'templates/emails',
    'tools',
];

foreach ($directories as $dir) {
    $target_dir = $dest_base . '/' . $dir;
    if (!is_dir($target_dir)) {
        echo "Creating directory: $target_dir\n";
        mkdir($target_dir, 0755, true);
    }
}

// Move files
foreach ($files_to_move as $src => $dest) {
    $src_file = $src_base . '/' . $src;
    $dest_file = $dest_base . '/' . $dest;
    
    if (file_exists($src_file)) {
        echo "Moving file: $src_file to $dest_file\n";
        
        // Ensure the destination directory exists
        $dest_dir = dirname($dest_file);
        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0755, true);
        }
        
        // Copy the file
        copy($src_file, $dest_file);
        
        // Optional: delete the original file
        unlink($src_file);
    } else {
        echo "Source file doesn't exist: $src_file\n";
    }
}

// Also move the check-syntax.php tool
$tools_src = $src_base . '/tools/check-syntax.php';
$tools_dest = $dest_base . '/tools/check-syntax.php';
if (file_exists($tools_src)) {
    echo "Moving file: $tools_src to $tools_dest\n";
    copy($tools_src, $tools_dest);
}

echo "File move completed!\n";
