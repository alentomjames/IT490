<?php

// Get the bundle name from the command line arguments
$bundleName = $argv[1];

if ($argc < 2) {
    echo "Please type it in the following format :D : php deployVersion.php [bundle]\n";
    exit(1);
}

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

// Adding functionality to send the currentVersion to the deployment machine using scp command
$deploymentUser = 'philzerin';
$deploymentHost = '172.29.82.171';
$deploymentPath = '/var/log/archive';

// Compress the file to be sent
$compressedFile = "apache_$newVersion.zip";
$compressedFilePath = "$currentPath/$compressedFile";
$command = "zip -r $compressedFilePath $newVersionPath";
exec($command, $output, $return);
if ($return === 0) {
    echo "Compressed apache_$newVersion to $compressedFile\n";
} else {
    echo "Failed to compress apache_$newVersion\n";
}

// Transfer the compressed file to the deployment machine using scp
$scpCommand = "scp $compressedFilePath $deploymentUser@$deploymentHost:$deploymentPath";
exec($scpCommand, $output, $return);
if ($return === 0){
    echo "Transferred $compressedFile to $deploymentUser@$deploymentHost:$deploymentPath\n";
} else {
    echo "Failed to transfer $compressedFile to $deploymentUser@$deploymentHost:$deploymentPath\n";
    exit(1);
}

?> 