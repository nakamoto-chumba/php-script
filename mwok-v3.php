<?php

// URL to download
/**
 * its v3 mwok tool able to download html and js on a scanned url
 * its writes them to a similar structure like only
 * its can be used to analyses web structure and retrives html and js
 */
$url = $argv[1];

// Check if URL is provided
if (empty($url)) {
    echo "Usage: php mwok.php <URL>\n";
    exit(1);
}

// Store the current working directory
$currentDir = getcwd();

// Remove protocol (http:// or https://) and domain from the URL
$pathOnly = parse_url($url, PHP_URL_PATH);
$domain = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
$initialDir = dirname($pathOnly);

// Create the directory structure locally relative to the current directory
$absoluteInitialDir = $currentDir . $initialDir;
if (!is_dir($absoluteInitialDir)) {
    mkdir($absoluteInitialDir, 0777, true);
}

// Download the file into the created directory structure
$fileContent = file_get_contents($url);
file_put_contents($currentDir . $pathOnly, $fileContent);

echo "File saved in: $currentDir$pathOnly\n";

// Check if the file is HTML
if (pathinfo($currentDir . $pathOnly, PATHINFO_EXTENSION) === 'html') {
    // Parse the HTML content to find links and script sources
    $dom = new DOMDocument();
    @$dom->loadHTML($fileContent); // Suppress errors for malformed HTML
    $links = [];
    $scripts = [];
    foreach ($dom->getElementsByTagName('a') as $link) {
        $href = $link->getAttribute('href');
        if (!empty($href) && strpos($href, '#') !== 0) {
            if (!filter_var($href, FILTER_VALIDATE_URL)) {
                $absoluteUrl = rtrim($domain . $initialDir, '/') . '/' . ltrim($href, '/');
                echo "$absoluteUrl ......new URL generated\n";
                if (file_get_contents($absoluteUrl) !== false) {
                    $links[] = $absoluteUrl;
                } else {
                    echo "Warning: Skipping invalid link: $absoluteUrl\n";
                }
            } else {
                $links[] = $href;
            }
        }
    }
    foreach ($dom->getElementsByTagName('script') as $script) {
        $src = $script->getAttribute('src');
        if (!empty($src) && !filter_var($src, FILTER_VALIDATE_URL)) {
            // Remove './' segment if present in the script source URL
            $src = str_replace('./', '', $src);
            $absoluteSrc = rtrim($domain . $initialDir, '/') . '/' . ltrim($src, '/');
            echo "$absoluteSrc ......new URL generated\n";
            if (file_get_contents($absoluteSrc) !== false) {
                $scripts[] = $absoluteSrc;
            } else {
                echo "Warning: Skipping invalid script source: $absoluteSrc\n";
            }
        } else {
            $scripts[] = $src;
        }
    }

    // Process links and scripts
    $processedLinks = [];
    foreach (array_merge($links, $scripts) as $link) {
        if (!in_array($link, $processedLinks)) {
            echo "Detected link: $link\n";
            $fileContent = file_get_contents($link);
            if ($fileContent !== false) {
                $filename = basename(parse_url($link, PHP_URL_PATH));
                file_put_contents($currentDir . $initialDir . '/' . $filename, $fileContent);
                echo "Content from link $link saved in: $currentDir$initialDir/$filename\n";
            } else {
                echo "Failed to retrieve content from link: $link\n";
            }
            $processedLinks[] = $link;
        }
    }
}
?>
