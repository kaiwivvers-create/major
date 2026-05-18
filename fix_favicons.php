<?php
$dir = __DIR__ . '/resources/views';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        if (str_contains($content, '<head>') && !str_contains($content, 'rel="icon"')) {
            $insert = "\n    @if (!empty(\$appBranding?->logo_url))\n        <link rel=\"icon\" href=\"{{ \$appBranding->logo_url }}\" type=\"image/x-icon\">\n    @endif";
            $newContent = str_replace('<head>', '<head>' . $insert, $content);
            file_put_contents($path, $newContent);
        }
    }
}
echo "Done!\n";
