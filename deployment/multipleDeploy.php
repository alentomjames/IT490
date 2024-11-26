<?php

// Initialize paths
$currentPath = '/var/log/current';
$archivePath = '/var/log/archive';
$sourcePath = '~/git/IT490';
$jsonConfig = 'bundles.json'; // Path to the JSON configuration file

// Parse JSON file
$jsonData = file_get_contents($jsonConfig);
$bundles = json_decode($jsonData, true);
if (!$bundles) {
    die("Failed to parse bundles JSON.\n");
}

// Helper function to copy directories
function copyDirectory($source, $destination) {
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    $files = scandir($source);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $sourceFile = $source . '/' . $file;
            $destinationFile = $destination . '/' . $file;
            if (is_dir($sourceFile)) {
                copyDirectory($sourceFile, $destinationFile);
            } else {
                if (!file_exists(dirname($destinationFile))) {
                    mkdir(dirname($destinationFile), 0755, true);
                }
                copy($sourceFile, $destinationFile);
            }
        }
    }
}

// Iterate through bundles and create directories
foreach ($bundles as $bundleName => $files) {
    $bundlePath = "$currentPath/$bundleName";

    // Create bundle directory
    if (!is_dir($bundlePath)) {
        mkdir($bundlePath, 0755, true);
        echo "Created directory: $bundlePath\n";
    }

    // Copy specified files into the bundle directory
    foreach ($files as $file) {
        $sourceFile = "$sourcePath/$file";
        $destinationFile = "$bundlePath/" . basename($file);

        if (file_exists($sourceFile)) {
            if (!file_exists(dirname($destinationFile))) {
                mkdir(dirname($destinationFile), 0755, true);
            }
            copy($sourceFile, $destinationFile);
            echo "Copied $sourceFile to $destinationFile\n";
        } else {
            echo "Warning: $sourceFile does not exist.\n";
        }
    }

    // Optional: Compress the bundle
    $compressedFile = "$bundleName.zip";
    $compressedFilePath = "$currentPath/$compressedFile";
    $command = "zip -r $compressedFilePath $bundlePath";
    exec($command, $output, $return);
    if ($return === 0) {
        echo "Compressed $bundleName to $compressedFile\n";
    } else {
        echo "Failed to compress $bundleName\n";
    }

    // Optional: Transfer the bundle to the deployment machine
    $deploymentUser = 'philzerin';
    $deploymentHost = '172.29.82.171';
    $deploymentPath = '/var/log/archive';
    $scpCommand = "scp $compressedFilePath $deploymentUser@$deploymentHost:$deploymentPath";
    exec($scpCommand, $output, $return);
    if ($return === 0) {
        echo "Transferred $compressedFile to $deploymentUser@$deploymentHost:$deploymentPath\n";
    } else {
        echo "Failed to transfer $compressedFile to $deploymentUser@$deploymentHost:$deploymentPath\n";
    }
}

?>
