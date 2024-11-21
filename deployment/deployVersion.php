<?php

// Initalizing the path for the deployment paths 
$currentPath = '/var/log/current';
$archivePath = '/var/log/archive';
$sourcePath = '/var/www/it490';

// Finding the latest version number
$latestVersion = 0;
$directoryHandle = opendir($currentPath);
while (($entry = readdir($directoryHandle)) !== false) {
    if (preg_match('/^apache_(\d+)$/', $entry, $matches)) {
        $versionNumber = (int)$matches[1];
        if ($versionNumber > $latestVersion) {
            $latestVersion = $versionNumber;
        }
    }
}
closedir($directoryHandle);

// Setting the previous version and the new version for comparison later
$previousVersion = $latestVersion;
$newVersion = $latestVersion + 1;

// Moving the current version folder to the archive
$currentVersionPath = "$currentPath/apache_$previousVersion";
$newVersionPath = "$currentPath/apache_$newVersion";

if (is_dir($currentVersionPath)) {
    $archiveVersionPath = "$archivePath/apache_$previousVersion";
    rename($currentVersionPath, $archiveVersionPath);
    echo "Moved apache_$previousVersion to archive.\n";
}


// Function to copy directories
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
                copy($sourceFile, $destinationFile);
            }
        }
    }
}

// Copying the new files from sourcePath to the new version directory
copyDirectory($sourcePath, $newVersionPath);
echo "Copied files to apache_$newVersion from $sourcePath to $newVersionPath\n";

// Comparing all files in the new version to the latest previous version in archive to create a changeLog.txt
if (is_dir($archiveVersionPath)) {
    $changelogPath = "$newVersionPath/changeLog_${newVersion}.txt";
    $command = "diff -ru $archiveVersionPath $newVersionPath > $changelogPath";
    exec($command, $output, $return);

    if ($return === 0) {
        echo "No differences found! No changeLog created.\n";
    } else {
        echo "Differences found, created a changelog: changeLog_${newVersion}.txt\n";
    }
}



?> 