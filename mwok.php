<?php

// URL to download
$url = $argv[1];

// Check if URL is provided
if (empty($url)) {
    echo "Usage: php mwok.php <URL>\n";
    exit(1);
}

// Store the current working directory
$currentDir = getcwd();

// Remove protocol (http:// or https://) and domain from the URL
$pathOnly = preg_replace('#^https?://[^/]+#', '', $url);

// Extract directory structure from the URL (excluding the last component which is the filename)
$dirStructure = dirname($pathOnly);

// Create the directory structure locally relative to the current directory
mkdir($currentDir . $dirStructure, 0777, true);

// Download the file into the created directory structure
file_put_contents($currentDir . $pathOnly, fopen($url, 'r'));

echo "File saved in: $currentDir$pathOnly\n";
