#!/usr/bin/env php
<?php
// code-check.php
// A CLI script to set up Composer-managed PHP linters and ESLint, then present a styled menu.

// ANSI color codes for styling
define('COLOR_RESET', "\033[0m");
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_CYAN', "\033[36m");
define('COLOR_RED', "\033[31m");

// Helper: run a shell command and return output and exit code.
function runCommand($command, &$output = null) {
    echo COLOR_YELLOW . "Running: $command" . COLOR_RESET . "\n";
    exec($command, $output, $exitCode);
    return $exitCode;
}

// Helper: output styled text.
function styledEcho($text, $color = COLOR_RESET) {
    echo $color . $text . COLOR_RESET . "\n";
}

// Check for Composer
if (runCommand('composer --version', $out) !== 0) {
    styledEcho("Composer is not installed or not in PATH. Exiting.", COLOR_RED);
    exit(1);
}

// Check for npm
if (runCommand('npm --version', $out) !== 0) {
    styledEcho("npm is not installed or not in PATH. Exiting.", COLOR_RED);
    exit(1);
}

$projectRoot = getcwd();
$composerJsonPath = $projectRoot . DIRECTORY_SEPARATOR . "composer.json";
$vendorDir = $projectRoot . DIRECTORY_SEPARATOR . "vendor";

// Create composer.json if missing
if (!file_exists($composerJsonPath)) {
    styledEcho("composer.json not found. Creating a minimal composer.json...", COLOR_CYAN);
    $composerJsonContent = '{
    "name": "yourname/yourproject",
    "description": "A project using PHP_CodeSniffer with WordPress Coding Standards, PHPCSUtils, PHPCSUtilsExtra, PHPStan, and PHP-CS-Fixer",
    "type": "project",
    "license": "proprietary",
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/PHPCSStandards/PHPCSUtils.git"
        },
        {
            "type": "vcs",
            "url": "https://github.com/PHPCSStandards/PHPCSUtilsExtra.git"
        }
    ],
    "require-dev": {
        "squizlabs/php_codesniffer": "^3.7",
        "wp-coding-standards/wpcs": "^2.3",
        "phpcsstandards/phpcsutils": "dev-stable",
        "phpcsstandards/phpcsutils-extra": "dev-stable",
        "phpstan/phpstan": "^1.9",
        "friendsofphp/php-cs-fixer": "^3.0"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}';
    // Write file as UTF-8 without BOM
    file_put_contents($composerJsonPath, $composerJsonContent);
} else {
    styledEcho("composer.json exists. Using existing file.", COLOR_CYAN);
}

// Run composer install
styledEcho("Running composer install...", COLOR_CYAN);
if (runCommand('composer install', $out) !== 0) {
    styledEcho("composer install failed. Exiting.", COLOR_RED);
    exit(1);
}

// Determine local executable paths (adjust for Windows if needed)
function getLocalPath($relativePath) {
    global $projectRoot;
    $path = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return file_exists($path) ? $path : null;
}

$phpcsPath = getLocalPath("vendor/bin/phpcs");
if (!$phpcsPath && file_exists(getLocalPath("vendor/bin/phpcs.bat"))) {
    $phpcsPath = getLocalPath("vendor/bin/phpcs.bat");
}
$phpcbfPath = getLocalPath("vendor/bin/phpcbf");
if (!$phpcbfPath && file_exists(getLocalPath("vendor/bin/phpcbf.bat"))) {
    $phpcbfPath = getLocalPath("vendor/bin/phpcbf.bat");
}
$phpstanPath = getLocalPath("vendor/bin/phpstan");
if (!$phpstanPath && file_exists(getLocalPath("vendor/bin/phpstan.bat"))) {
    $phpstanPath = getLocalPath("vendor/bin/phpstan.bat");
}
$phpCsFixerPath = getLocalPath("vendor/bin/php-cs-fixer");
if (!$phpCsFixerPath && file_exists(getLocalPath("vendor/bin/php-cs-fixer.bat"))) {
    $phpCsFixerPath = getLocalPath("vendor/bin/php-cs-fixer.bat");
}

if (!$phpcsPath || !$phpcbfPath || !$phpstanPath || !$phpCsFixerPath) {
    styledEcho("One or more local executables could not be found. Check your Composer installation.", COLOR_RED);
    exit(1);
}

// Configure PHPCS installed_paths to use WPCS and PHPCSUtils.
// (WPCS is installed to vendor/wp-coding-standards/wpcs and PHPCSUtils to vendor/wp-coding-standards/phpcsutils)
$wpcsPath = $projectRoot . DIRECTORY_SEPARATOR . "vendor/wp-coding-standards/wpcs";
$phpcsutilsPath = $projectRoot . DIRECTORY_SEPARATOR . "vendor/wp-coding-standards/phpcsutils";
$installedPaths = $wpcsPath . "," . $phpcsutilsPath;
runCommand("$phpcsPath --config-set installed_paths \"$installedPaths\"");
$configuredPaths = shell_exec("$phpcsPath --config-show installed_paths");
styledEcho("PHPCS installed_paths configured as: " . trim($configuredPaths), COLOR_GREEN);

// Ensure ESLint is installed globally (if not, install with npm)
if (runCommand('eslint --version', $out) !== 0) {
    styledEcho("ESLint not found. Installing globally with eslint-plugin-jquery...", COLOR_CYAN);
    if (runCommand('npm install -g eslint eslint-plugin-jquery', $out) !== 0) {
        styledEcho("Failed to install ESLint globally. Exiting.", COLOR_RED);
        exit(1);
    }
} else {
    styledEcho("ESLint is already installed globally.", COLOR_GREEN);
}

// Set up error log file
$ERROR_LOG = $projectRoot . DIRECTORY_SEPARATOR . "error_log.txt";

// Define linter functions.
function logError($message) {
    global $ERROR_LOG;
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($ERROR_LOG, "[$timestamp] $message\n", FILE_APPEND);
}

function runLinter($label, $command) {
    styledEcho("[$label] Running...", COLOR_CYAN);
    $output = [];
    exec($command . " 2>&1", $output, $exitCode);
    $result = implode("\n", $output);
    echo $result . "\n";
    if ($exitCode !== 0) {
        logError("$label errors:\n$result");
    }
}

function runAllLinters() {
    // Run PHP-CS-Fixer first to auto-fix code style issues.
    runLinter("PHP-CS-Fixer", escapeshellcmd($GLOBALS['phpCsFixerPath'] . " fix ."));
    runLinter("PHPCS", escapeshellcmd($GLOBALS['phpcsPath'] . " --standard=WordPress ."));
    runLinter("PHPCBF", escapeshellcmd($GLOBALS['phpcbfPath'] . " --standard=WordPress ."));
    // PHPLint: loop over PHP files
    styledEcho("[PHPLint] Running...", COLOR_CYAN);
    $files = glob_recursive("*.php", $GLOBALS['projectRoot']);
    foreach ($files as $file) {
        runLinter("PHPLint ($file)", "php -l " . escapeshellarg($file));
    }
    runLinter("ESLint", "eslint --fix .");
    runLinter("PHPStan", escapeshellcmd($GLOBALS['phpstanPath'] . " analyse --level=8 ."));
    styledEcho("All linters finished.", COLOR_GREEN);
}

function glob_recursive($pattern, $path) {
    $files = glob($path . DIRECTORY_SEPARATOR . $pattern);
    foreach (glob($path . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $dir) {
        $files = array_merge($files, glob_recursive($pattern, $dir));
    }
    return $files;
}

// Display styled menu.
function showMenu() {
    echo COLOR_GREEN . "====================================\n" . COLOR_RESET;
    echo COLOR_CYAN . "      Code Checking Menu (PHP and JS Only)\n" . COLOR_RESET;
    echo COLOR_GREEN . "====================================\n" . COLOR_RESET;
    echo COLOR_YELLOW . "1) Run PHP_CodeSniffer (PHPCS v4) - WordPress Standard\n" . COLOR_RESET;
    echo COLOR_YELLOW . "2) Run PHP Code Beautifier (PHPCBF v4) - WordPress Standard\n" . COLOR_RESET;
    echo COLOR_YELLOW . "3) Run PHP Syntax Checker (PHPLint)\n" . COLOR_RESET;
    echo COLOR_YELLOW . "4) Run ESLint (JavaScript Linter)\n" . COLOR_RESET;
    echo COLOR_YELLOW . "5) Run PHP-CS-Fixer (Auto-fix Code Style)\n" . COLOR_RESET;
    echo COLOR_YELLOW . "6) Run PHPStan (Static Analysis)\n" . COLOR_RESET;
    echo COLOR_YELLOW . "7) Run All Linters\n" . COLOR_RESET;
    echo COLOR_YELLOW . "8) View Error Log\n" . COLOR_RESET;
    echo COLOR_YELLOW . "9) Exit and Clean Up Composer Files\n" . COLOR_RESET;
    echo COLOR_GREEN . "====================================\n" . COLOR_RESET;
    echo "Choose an option (1-9): ";
}

// Main menu loop.
while (true) {
    showMenu();
    $handle = fopen("php://stdin", "r");
    $choice = trim(fgets($handle));
    switch ($choice) {
        case '1':
            runLinter("PHPCS", escapeshellcmd($phpcsPath . " --standard=WordPress ."));
            break;
        case '2':
            runLinter("PHPCBF", escapeshellcmd($phpcbfPath . " --standard=WordPress ."));
            break;
        case '3':
            // Run PHPLint on each PHP file recursively.
            styledEcho("[PHPLint] Running...", COLOR_CYAN);
            $files = glob_recursive("*.php", $projectRoot);
            foreach ($files as $file) {
                runLinter("PHPLint ($file)", "php -l " . escapeshellarg($file));
            }
            break;
        case '4':
            runLinter("ESLint", "eslint --fix .");
            break;
        case '5':
            runLinter("PHP-CS-Fixer", escapeshellcmd($phpCsFixerPath . " fix ."));
            break;
        case '6':
            runLinter("PHPStan", escapeshellcmd($phpstanPath . " analyse --level=8 ."));
            break;
        case '7':
            runAllLinters();
            break;
        case '8':
            if (file_exists($ERROR_LOG)) {
                styledEcho("Error Log:", COLOR_CYAN);
                echo file_get_contents($ERROR_LOG) . "\n";
            } else {
                styledEcho("No errors logged yet.", COLOR_GREEN);
            }
            break;
        case '9':
            styledEcho("Exiting...", COLOR_CYAN);
            styledEcho("Clearing Composer cache...", COLOR_CYAN);
            runCommand("composer clear-cache");
            if (file_exists($composerJsonPath)) {
                styledEcho("Deleting composer.json...", COLOR_CYAN);
                unlink($composerJsonPath);
            }
            if (is_dir($vendorDir)) {
                styledEcho("Deleting vendor directory...", COLOR_CYAN);
                // Recursively remove vendor directory
                function rrmdir($dir) {
                    foreach (glob($dir . '/*') as $file) {
                        if (is_dir($file))
                            rrmdir($file);
                        else
                            unlink($file);
                    }
                    rmdir($dir);
                }
                rrmdir($vendorDir);
            }
            exit;
        default:
            styledEcho("Invalid option! Please choose a number from 1 to 9.", COLOR_RED);
    }
    echo "Press Enter to continue...";
    fgets($handle);
    fclose($handle);
}
