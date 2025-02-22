#!/bin/bash

echo "🚀 Dev Tools Container Ready! Running Tests..."

function run_tests {
    echo "==================================="
    echo "📌 Select a Test to Run:"
    echo "==================================="
    echo "1️⃣ Run Syntax Check (PHPLint)"
    echo "2️⃣ Run Coding Standards Check (PHPCS)"
    echo "3️⃣ Run Static Analysis (PHPStan)"
    echo "4️⃣ Fix Code Automatically (PHP-CS-Fixer, PHPCBF)"
    echo "5️⃣ Run Unit Tests (PHPUnit)"
    echo "6️⃣ Run JavaScript Linting (ESLint)"
    echo "7️⃣ Run All Tests (Recommended Order)"
    echo "0️⃣ Exit"
    echo "==================================="

    read -p "Enter your choice (0-7): " choice

    case $choice in
        1)
            echo "🔍 Running Syntax Check (PHPLint)..."
            phplint .
            ;;
        2)
            echo "🔍 Running Coding Standards Check (PHPCS)..."
            phpcs . --standard=PSR12
            ;;
        3)
            echo "🔍 Running Static Analysis (PHPStan)..."
            phpstan analyse . --level=5
            ;;
        4)
            echo "🛠 Fixing Code Automatically..."
            phpcbf . --standard=PSR12
            php-cs-fixer fix .
            ;;
        5)
            echo "🧪 Running Unit Tests (PHPUnit)..."
            phpunit tests/
            ;;
        6)
            echo "🧹 Running JavaScript Linting (ESLint)..."
            eslint .
            ;;
        7)
            echo "🚀 Running All Tests in Optimized Order..."
            phplint .
            phpcs . --standard=PSR12
            phpstan analyse . --level=5
            phpcbf . --standard=PSR12
            php-cs-fixer fix .
            phpunit tests/
            eslint .
            ;;
        0)
            echo "👋 Exiting..."
            exit 0
            ;;
        *)
            echo "❌ Invalid choice. Please enter a number between 0-7."
            ;;
    esac
}

while true; do
    run_tests
done
