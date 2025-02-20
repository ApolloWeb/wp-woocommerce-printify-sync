<?php
// Define color codes
define('COLOR_RESET', "\033[0m");
define('COLOR_RED', "\033[31m");
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_CYAN', "\033[36m");

// Get the current working directory (root folder)
$current_dir = getcwd();

// Function to display messages in color
function print_color($color, $message)
{
    echo $color . $message . COLOR_RESET . "\n";
}

// Function to install PHP tools globally via Composer
function install_php_tool($tool_name, $command)
{
    print_color(COLOR_YELLOW, "Installing $tool_name globally...");
    $output = shell_exec($command);
    print_color(COLOR_GREEN, "$tool_name installed globally.\n");
}

// Function to install JavaScript tools globally via NPM
function install_js_tool($tool_name, $command)
{
    print_color(COLOR_YELLOW, "Installing $tool_name globally via npm...");
    $output = shell_exec($command);
    print_color(COLOR_GREEN, "$tool_name installed globally via npm.\n");
}

// Display a menu of available tools
function show_menu()
{
    print_color(COLOR_BLUE, "Choose a tool to configure:");
    print_color(COLOR_YELLOW, "1. PHPCS (WordPress standard)");
    print_color(COLOR_YELLOW, "2. PHPStan");
    print_color(COLOR_YELLOW, "3. ESLint");
    print_color(COLOR_YELLOW, "4. Exit");
}

// Get user choice
function get_user_choice()
{
    print_color(COLOR_CYAN, "Enter your choice (1-4): ");
    $choice = fgets(STDIN);  // Get user input from the command line
    $choice = trim($choice); // Clean up input
    return $choice;
}

// Ask for the path to the selected tool (optional for local installs, otherwise use global)
function prompt_for_tool_path($tool_name)
{
    print_color(COLOR_CYAN, "Enter the path to your $tool_name tool (or leave empty for global installation): ");
    $path = fgets(STDIN);  // Get user input from the command line
    $path = trim($path); // Clean up input
    return $path;
}

// Check if a PHP tool is installed globally (using Composer)
function is_php_tool_installed($tool_name)
{
    $check_command = "composer global show $tool_name";
    $output = shell_exec($check_command);
    return strpos($output, 'versions') !== false;
}

// Check if ESLint is installed globally (using npm)
function is_js_tool_installed($tool_name)
{
    $check_command = "npm list -g --depth=0 $tool_name";
    $output = shell_exec($check_command);
    return strpos($output, $tool_name) !== false;
}

// Function to simulate progress for long-running tasks
function simulate_progress($tool_name)
{
    print_color(COLOR_YELLOW, "Running $tool_name... Please wait.\n");
    for ($i = 0; $i < 10; $i++) {
        echo ".";
        usleep(500000);  // Sleep for 0.5 seconds to simulate progress
    }
    echo "\n";
}

// Main program loop
while (true) {
    show_menu();
    $choice = get_user_choice();

    switch ($choice) {
    case '1':
        // PHPCS with WordPress standard
        $path = prompt_for_tool_path("PHPCS");
        if (empty($path)) {
            if (!is_php_tool_installed('squizlabs/php_codesniffer')) {
                install_php_tool('PHPCS', 'composer global require squizlabs/php_codesniffer');
            }
            // Install WordPress coding standards if needed
            if (!is_php_tool_installed('wp-coding-standards/wpcs')) {
                install_php_tool('WordPress Coding Standards', 'composer global require wp-coding-standards/wpcs');
            }

            // Run PHPCS with WordPress standard globally from the current working directory
            simulate_progress("PHPCS (WordPress standard)");
            print_color(COLOR_GREEN, "Running PHPCS with WordPress coding standard on the directory for PHP files...");
            shell_exec("php $(composer global config bin-dir)/phpcs --standard=WordPress $current_dir/*.php"); // Restrict to PHP files
            print_color(COLOR_GREEN, "PHPCS completed.");

            // Run PHPCBF to fix the detected issues
            simulate_progress("PHPCBF (WordPress standard)");
            print_color(COLOR_GREEN, "Running PHPCBF to fix PHP file issues...");
            shell_exec("php $(composer global config bin-dir)/phpcbf --standard=WordPress $current_dir/*.php"); // Fix violations
            print_color(COLOR_GREEN, "PHPCBF completed.");

            // Optionally, run PHP-CS-Fixer for additional fixes
            $php_cs_fixer_path = prompt_for_tool_path("PHP-CS-Fixer");
            if (empty($php_cs_fixer_path)) {
                if (!is_php_tool_installed('friendsofphp/php-cs-fixer')) {
                    install_php_tool('PHP-CS-Fixer', 'composer global require friendsofphp/php-cs-fixer');
                }
                // Run PHP-CS-Fixer globally from the current working directory
                simulate_progress("PHP-CS-Fixer");
                print_color(COLOR_GREEN, "Running PHP-CS-Fixer to further fix PHP files...");
                shell_exec("php $(composer global config bin-dir)/php-cs-fixer fix $current_dir/*.php"); // Fix using PHP-CS-Fixer
                print_color(COLOR_GREEN, "PHP-CS-Fixer completed.");
            } else {
                // Run PHP-CS-Fixer from custom path
                simulate_progress("PHP-CS-Fixer");
                print_color(COLOR_GREEN, "Running PHP-CS-Fixer to further fix PHP files...");
                shell_exec("php $php_cs_fixer_path fix $current_dir/*.php"); // Fix using PHP-CS-Fixer
                print_color(COLOR_GREEN, "PHP-CS-Fixer completed.");
            }

        } else {
            // Similar handling as above, but using custom path for PHPCS and PHPCBF
            simulate_progress("PHPCS (WordPress standard)");
            print_color(COLOR_GREEN, "Running PHPCS with WordPress coding standard on the directory for PHP files...");
            shell_exec("php $path --standard=WordPress $current_dir/*.php");
            print_color(COLOR_GREEN, "PHPCS completed.");

            simulate_progress("PHPCBF (WordPress standard)");
            print_color(COLOR_GREEN, "Running PHPCBF to fix PHP file issues...");
            shell_exec("php $path --standard=WordPress $current_dir/*.php");
            print_color(COLOR_GREEN, "PHPCBF completed.");

            // Optionally, run PHP-CS-Fixer if desired
            $php_cs_fixer_path = prompt_for_tool_path("PHP-CS-Fixer");
            if (empty($php_cs_fixer_path)) {
                if (!is_php_tool_installed('friendsofphp/php-cs-fixer')) {
                    install_php_tool('PHP-CS-Fixer', 'composer global require friendsofphp/php-cs-fixer');
                }
                // Run PHP-CS-Fixer globally from the current working directory
                simulate_progress("PHP-CS-Fixer");
                print_color(COLOR_GREEN, "Running PHP-CS-Fixer to further fix PHP files...");
                shell_exec("php $(composer global config bin-dir)/php-cs-fixer fix $current_dir/*.php");
                print_color(COLOR_GREEN, "PHP-CS-Fixer completed.");
            } else {
                // Run PHP-CS-Fixer from custom path
                simulate_progress("PHP-CS-Fixer");
                print_color(COLOR_GREEN, "Running PHP-CS-Fixer to further fix PHP files...");
                shell_exec("php $php_cs_fixer_path fix $current_dir/*.php");
                print_color(COLOR_GREEN, "PHP-CS-Fixer completed.");
            }
        }
        break;

    case '2':
        // PHPStan
        $path = prompt_for_tool_path("PHPStan");
        if (empty($path)) {
            if (!is_php_tool_installed('phpstan/phpstan')) {
                install_php_tool('PHPStan', 'composer global require phpstan/phpstan --dev');
            }
            // Run PHPStan globally from the current working directory
            simulate_progress("PHPStan");
            print_color(COLOR_GREEN, "Running PHPStan on the directory for PHP files...");
            shell_exec("php $(composer global config bin-dir)/phpstan analyse --level=max $current_dir/*.php"); // Restrict to PHP files
            print_color(COLOR_GREEN, "PHPStan completed.");
        } else {
            // Run PHPStan from custom path
            simulate_progress("PHPStan");
            print_color(COLOR_GREEN, "Running PHPStan on the directory for PHP files...");
            shell_exec("php $path analyse --level=max $current_dir/*.php");
            print_color(COLOR_GREEN, "PHPStan completed.");
        }
        break;

    case '3':
        // ESLint
        $path = prompt_for_tool_path("ESLint");
        if (empty($path)) {
            if (!is_js_tool_installed('eslint')) {
                install_js_tool('ESLint', 'npm install -g eslint');
            }
            // Run ESLint globally from the current working directory
            simulate_progress("ESLint");
            print_color(COLOR_GREEN, "Running ESLint on the directory for JavaScript files...");
            shell_exec("npx eslint $current_dir/**/*.js"); // Lint all .js files in current directory and subdirectories
            print_color(COLOR_GREEN, "ESLint completed.");
        } else {
            // Run ESLint from custom path
            simulate_progress("ESLint");
            print_color(COLOR_GREEN, "Running ESLint on the directory for JavaScript files...");
            shell_exec("npx eslint $path/**/*.js"); // Lint all .js files in specified path
            print_color(COLOR_GREEN, "ESLint completed.");
        }
        break;

    case '4':
        // Exit the program
        print_color(COLOR_BLUE, "Exiting the program.");
        exit(0);

    default:
        print_color(COLOR_RED, "Invalid choice. Please select a valid option.");
        break;
    }
}
