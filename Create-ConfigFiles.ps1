# PowerShell Script to Create Configuration Files

# Function to Create File if it Doesn't Exist
function Create-File {
    param (
        [string]$FilePath,
        [string]$Content
    )
    if (-Not (Test-Path $FilePath)) {
        $Content | Out-File -FilePath $FilePath -Encoding utf8
        Write-Host "✅ Created: $FilePath"
    } else {
        Write-Host "⚠️ Skipped: $FilePath (Already Exists)"
    }
}

# 1️⃣ Create `.phpcs.xml` (WordPress Coding Standards)
$phpcsConfig = @"
<?xml version="1.0"?>
<ruleset name="WordPress Coding Standards">
    <description>PHP_CodeSniffer rules for WordPress</description>
    <rule ref="WordPress-Core"/>
    <rule ref="WordPress-Docs"/>
    <rule ref="WordPress-Extra"/>
    <rule ref="WordPress-VIP-Go"/>
    <exclude-pattern>vendor/*</exclude-pattern>
</ruleset>
"@
Create-File -FilePath ".phpcs.xml" -Content $phpcsConfig

# 2️⃣ Create `.php-cs-fixer.php` (PHP-CS-Fixer Configuration)
$phpCsFixerConfig = @"
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->notPath('wp-config.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'align_single_space'],
        'blank_line_after_opening_tag' => true,
        'braces' => true,
        'concat_space' => ['spacing' => 'one'],
    ])
    ->setFinder($finder);
"@
Create-File -FilePath ".php-cs-fixer.php" -Content $phpCsFixerConfig

# 3️⃣ Create `phpstan.neon` (PHPStan Configuration)
$phpstanConfig = @"
includes:
    - vendor/phpstan/phpstan/conf/strict-rules.neon

parameters:
    level: max
    paths:
        - .
    excludePaths:
        - vendor/*
        - node_modules/*
"@
Create-File -FilePath "phpstan.neon" -Content $phpstanConfig

# 4️⃣ Create `eslint.config.js` (ESLint Configuration)
$eslintConfig = @"
import js from "@eslint/js";
import recommended from "eslint-config-recommended";

export default [
  js.configs.recommended,
  recommended,
  {
    rules: {
      "no-unused-vars": "warn",
      "no-console": "off",
      "indent": ["error", 2],
      "quotes": ["error", "double"],
    },
  },
];
"@
Create-File -FilePath "eslint.config.js" -Content $eslintConfig

Write-Host "`n🚀 All configuration files are set up!"
