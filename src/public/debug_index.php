<?php
/**
 * Debug wrapper for index.php
 * This file will help diagnose path issues in the Docker environment
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define possible paths to the bootstrap file
$possible_bootstrap_paths = [
    '../app/bootstrap.php',
    '/var/www/html/app/bootstrap.php',
    '/var/www/html/src/app/bootstrap.php',
    __DIR__ . '/../app/bootstrap.php',
    __DIR__ . '/../../app/bootstrap.php',
];

// Debug information
echo "<!-- Current directory: " . __DIR__ . " -->\n";
echo "<!-- Checking for bootstrap.php file... -->\n";

// Function to check file existence
function file_exists_and_readable($path) {
    $exists = file_exists($path);
    $readable = is_readable($path);
    echo "<!-- Checking path: $path | Exists: " . ($exists ? 'Yes' : 'No') . 
         " | Readable: " . ($readable ? 'Yes' : 'No') . " -->\n";
    return $exists && $readable;
}

// Try to include the bootstrap file from one of the possible paths
$bootstrap_included = false;

foreach ($possible_bootstrap_paths as $path) {
    if (file_exists_and_readable($path)) {
        echo "<!-- Found bootstrap at: $path -->\n";
        // Include the main index.php with the correct bootstrap path
        define('BOOTSTRAP_PATH', $path);
        require_once __DIR__ . '/index.php.orig';
        $bootstrap_included = true;
        break;
    }
}

// If bootstrap wasn't found, display a detailed error
if (!$bootstrap_included) {
    echo "<h1>Error: Cannot locate bootstrap.php</h1>";
    echo "<p>The application is unable to find the bootstrap.php file. Please check the file structure.</p>";
    echo "<h2>Directory Structure</h2>";
    echo "<pre>";
    
    // Show the directory structure using PHP functions
    echo "Search for bootstrap.php:\n";
    $bootstrap_files = [];
    $directory = '/var/www/html';
    if (is_dir($directory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->getFilename() === 'bootstrap.php') {
                $bootstrap_files[] = $file->getPathname();
            }
        }
    }
    if (!empty($bootstrap_files)) {
        foreach ($bootstrap_files as $file) {
            echo htmlspecialchars($file) . "\n";
        }
    } else {
        echo "No results found\n";
    }
    echo "\n";

    echo "Directory structure:\n";
    $dirs = [];
    if (is_dir($directory)) {
        $dirIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($dirIterator as $file) {
            if ($file->isDir()) {
                $dirs[] = $file->getPathname();
            }
        }
        sort($dirs);
    }
    if (!empty($dirs)) {
        foreach ($dirs as $dir) {
            echo htmlspecialchars($dir) . "\n";
        }
    } else {
        echo "No results found";
    }
        foreach ($iterator as $file) {
            if ($file->getFilename() === 'bootstrap.php') {
                $bootstrapFiles[] = $file->getPathname();
            }
        }
    }
    if (!empty($bootstrapFiles)) {
        foreach ($bootstrapFiles as $file) {
            echo htmlspecialchars($file) . "\n";
        }
    } else {
        echo "No results found\n";
    }
    echo "\n";

    echo "Directory structure:\n";
    function listDirectories($dir, $prefix = '') {
        if (!is_dir($dir)) return;
        echo htmlspecialchars($prefix . $dir) . "\n";
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                listDirectories($path, $prefix . '  ');
            }
        }
    }
    listDirectories($directory);
    
    echo "</pre>";
    
    // List environment variables
    echo "<h2>Environment Variables</h2>";
    echo "<pre>";
    $env_vars = $_ENV;
    foreach ($env_vars as $key => $value) {
        if (strpos(strtolower($key), 'password') === false && 
            strpos(strtolower($key), 'secret') === false) {
            echo htmlspecialchars("$key=$value") . "\n";
        }
    }
    echo "</pre>";
    
    exit(1);
}
