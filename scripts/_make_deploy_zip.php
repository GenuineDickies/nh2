<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$zipPath = $argv[1] ?? ($root . '/newhope-deploy.zip');

@unlink($zipPath);

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Failed to create zip at {$zipPath}\n");
    exit(1);
}

// (source path relative to project root => target path in zip)
$mappings = [
    'app' => 'app',
    'database' => 'database',
    'scripts' => 'scripts',
    'public' => 'public_html',
];

// Files to exclude inside any of the source trees.
$excludes = [
    '_make_deploy_zip.php',
];

$addedCount = 0;
foreach ($mappings as $sourceRel => $zipPrefix) {
    $sourceDir = $root . '/' . $sourceRel;
    if (!is_dir($sourceDir)) {
        fwrite(STDERR, "Source missing: {$sourceDir}\n");
        continue;
    }

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iter as $file) {
        /** @var SplFileInfo $file */
        if ($file->isDir()) {
            continue;
        }
        $base = $file->getBasename();
        if (in_array($base, $excludes, true)) {
            continue;
        }
        $absPath = $file->getPathname();
        // Build the zip-relative path with forward slashes (zip spec requires this).
        $relative = ltrim(str_replace('\\', '/', substr($absPath, strlen($sourceDir))), '/');
        $zipEntry = $zipPrefix . '/' . $relative;
        $zip->addFile($absPath, $zipEntry);
        $addedCount++;
    }
}

if ($zip->close() !== true) {
    fwrite(STDERR, "Failed to close zip\n");
    exit(1);
}

printf("Wrote %s (%d files, %s bytes)\n", $zipPath, $addedCount, number_format(filesize($zipPath)));
