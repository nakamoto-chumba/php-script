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
    // Parse the HTML content to find links, script sources, CSS files, and images
    $dom = new DOMDocument();
    @$dom->loadHTML($fileContent); // Suppress errors for malformed HTML

    $resources = [];

    // Links
    foreach ($dom->getElementsByTagName('a') as $link) {
        $href = $link->getAttribute('href');
        if (!empty($href) && strpos($href, '#') !== 0) {
            $absoluteUrl = rtrim($domain . $initialDir, '/') . '/' . ltrim($href, '/');
            echo "New HTML link: $absoluteUrl\n";
            $resources[] = $absoluteUrl;
        }
    }

    // Scripts
    foreach ($dom->getElementsByTagName('script') as $script) {
        $src = $script->getAttribute('src');
        if (!empty($src)) {
            // Remove './' segment if present in the script source URL
            $src = str_replace('./', '', $src);
            $absoluteSrc = rtrim($domain . $initialDir, '/') . '/' . ltrim($src, '/');
            echo "New script link: $absoluteSrc\n";
            $resources[] = $absoluteSrc;
        }
    }

    // CSS files
    foreach ($dom->getElementsByTagName('link') as $css) {
        if ($css->getAttribute('rel') == 'stylesheet') {
            $href = $css->getAttribute('href');
            if (!empty($href)) {
                // Remove './' segment if present in the CSS file URL
                $href = str_replace('./', '', $href);
                $absoluteHref = rtrim($domain . $initialDir, '/') . '/' . ltrim($href, '/');
                echo "New CSS link: $absoluteHref\n";
                $resources[] = $absoluteHref;
            }
        }
    }

    // Images
    foreach ($dom->getElementsByTagName('img') as $img) {
        $src = $img->getAttribute('src');
        if (!empty($src)) {
            // Remove './' segment if present in the image URL
            $src = str_replace('./', '', $src);
            $absoluteSrc = rtrim($domain . $initialDir, '/') . '/' . ltrim($src, '/');
            echo "New image link: $absoluteSrc\n";
            $resources[] = $absoluteSrc;
        }
    }

    // Download resources
    foreach ($resources as $resource) {
        $fileContent = @file_get_contents($resource);
        if ($fileContent !== false) {
            $filename = basename($resource);
            file_put_contents($currentDir . $initialDir . '/' . $filename, $fileContent);
            echo "Resource saved in: $currentDir$initialDir/$filename\n";
        } else {
            echo "Failed to retrieve resource: $resource\n";
        }
    }
}
?>
