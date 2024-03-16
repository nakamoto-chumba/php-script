<?php

// URL to download
$url = $argv[1];
/***
 * 
 * this version only generate html docs from url and 
 * any link on the document
 */
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
    // Parse the HTML content to find links
    $dom = new DOMDocument();
    @$dom->loadHTML($fileContent); // Suppress errors for malformed HTML
    $links = [];
    foreach ($dom->getElementsByTagName('a') as $link) {
        $href = $link->getAttribute('href');
        // Check if the link is not empty and not an anchor
        if (!empty($href) && strpos($href, '#') !== 0) {
            // If the link is relative, construct the absolute URL
            if (!filter_var($href, FILTER_VALIDATE_URL)) {
                // Construct the absolute URL
                $absoluteUrl = rtrim($domain . $initialDir, '/') . '/' . ltrim($href, '/');
                echo "$absoluteUrl ......new url generated\n";
                // Check if the absolute URL exists
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

    // Process each unique link
    $processedLinks = [];
    foreach ($links as $link) {
        // Check if link has already been processed
        if (!in_array($link, $processedLinks)) {
            echo "Detected link: $link\n";
            // Retrieve content of linked file
            $linkedFileContent = file_get_contents($link);
            if ($linkedFileContent !== false) {
                // Get the file name from the URL
                $filename = basename(parse_url($link, PHP_URL_PATH));
                // Save the content to a file
                file_put_contents($currentDir . $initialDir . '/' . $filename, $linkedFileContent);
                echo "Content from link $link saved in: $currentDir$initialDir/$filename\n";
            } else {
                echo "Failed to retrieve content from link: $link\n";
            }
            // Add link to processed links list
            $processedLinks[] = $link;
        }
    }
}
