<?php
// Get the bundle name from the command line arguments
$bundleName = $argv[1];

if ($argc < 2) {
    echo "Please type it in the following format :D : php deployVersion.php [bundle]\n
    Example: php deployVersion.php login\n";
    exit(1);
}

// Initalizing the path for the deployment paths 
$currentPath = '/var/log/current';
$archivePath = '/var/log/archive';
$sourcePath = '/var/www/it490';

// Path to the config.ini file
$configIniPath = '/var/log/config.ini';

// Parse the config.ini file and check if the bundle exists 
$config = parse_ini_file($configIniPath, true);
if (!isset($config[$bundleName])) {
    echo "Bundle '$bundleName' not found in config file.\n";
    exit(1);
}

// Get the list of files for the bundle
$filesToDeploy = (array) $config[$bundleName];
// Finding the latest version number
$latestVersion = 0;
$directoryHandle = opendir($currentPath);
$versionPattern = '/^' . preg_quote($bundleName, '/') . '_(\d+)$/';

while (($entry = readdir($directoryHandle)) !== false) {
    if (preg_match($versionPattern, $entry, $matches)) {
        $versionNumber = (int)$matches[1];
        if ($versionNumber > $latestVersion) {
            $latestVersion = $versionNumber;
        }
    }
}
closedir($directoryHandle);

// Changing copyDirectory to copyFiles to only get the files that are within the requested bundle
function copyFiles($files, $sourceBasePath, $destinationBasePath) {
    foreach ($files as $relativePath) {
        $sourceFile = $sourceBasePath . $relativePath;
        $destinationFile = $destinationBasePath . $relativePath;

        // Ensure the destination directory exists
        $destinationDir = dirname($destinationFile);
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        if (!copy($sourceFile, $destinationFile)) {
            echo "Failed to copy $sourceFile to $destinationFile\n";
        } else {
            echo "Copied $sourceFile to $destinationFile\n";
        }
    }
}

copyFiles($filesToDeploy, $sourcePath, $newVersionPath);
echo "Copied bundle '$bundleName' files to {$bundleName}_$newVersion in $newVersionPath\n";

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
$compressedFile = "{$bundleName}_{$newVersion}.zip";
$compressedFilePath = "$currentPath/$compressedFile";

// Create a zip archive of the new version folder
$command = "cd $currentPath && zip -r $compressedFile {$bundleName}_$newVersion";
exec($command, $output, $return);
if ($return === 0) {
    echo "Compressed {$bundleName}_$newVersion to $compressedFile\n";
} else {
    echo "Failed to compress {$bundleName}_$newVersion\n";
    exit(1);
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
