<?php

// Define ANSI color codes for terminal output
const COLOR_RED = "\033[31m";
const COLOR_GREEN = "\033[32m";
const COLOR_YELLOW = "\033[33m";
const COLOR_RESET = "\033[0m";

$config_file = "config.json";

function print_color($color, $message) {
    echo $color . $message . COLOR_RESET . "\n";
}

function default_config($config_file) {
    if (!file_exists($config_file)) {
        $default_settings = [
            "setting1" => "default_value1",
            "setting2" => "default_value2"
        ];
        if (file_put_contents($config_file, json_encode($default_settings, JSON_PRETTY_PRINT)) === false) {
            print_color(COLOR_RED, "Error: Unable to write default config file.");
            return false;
        }
        print_color(COLOR_GREEN, "Default config file created.");
    }
    return true;
}

function load_config($config_file) {
    if (!file_exists($config_file)) {
        print_color(COLOR_RED, "Error: Config file missing.");
        return null;
    }
    
    $config_data = json_decode(file_get_contents($config_file), true);
    if (!is_array($config_data)) {
        print_color(COLOR_RED, "Error: Config file is invalid or corrupted.");
        return null;
    }
    
    return $config_data;
}

// Increase memory limit if needed
ini_set('memory_limit', '512M');

default_config($config_file);
$config = load_config($config_file);

if ($config !== null) {
    print_color(COLOR_YELLOW, "Loaded config:");
    print_r($config);
} else {
    print_color(COLOR_RED, "Failed to load config.");
}
