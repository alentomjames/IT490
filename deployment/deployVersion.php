<?php
require_once '../webserver/rabbitmq_connection.php'; // RabbitMQ connection
require_once '../webserver/vendor/autoload.php';
use PhpAmpqLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

if ($argc < 3) {
    echo "Please type it in the following format: php deployVersion.php [bundle] [machine]\n";
    exit(1);
}

$bundleName = $argv[1];
$machineName = $argv[2];
// Initializing the paths for deployment
$currentPath = '/var/log/current';
$archivePath = '/var/log/archive';
$sourcePath = '/home/alen/git/IT490/';

// Path to the config.ini file
$configIniPath = '/var/log/config.ini';

// Parse the config.ini file and check if the bundle exists
$config = parse_ini_file($configIniPath, true);
if ($config === false) {
    echo "Failed to parse config file at $configIniPath\n";
    exit(1);
}
if (!isset($config[$bundleName])) {
    echo "Bundle '$bundleName' not found in config file.\n";
    exit(1);
}

// Get the list of files for the bundle
if (isset($config[$bundleName]['file'])) {
    $filesToDeploy = $config[$bundleName]['file'];
} else {
    echo "No files to deploy for bundle '$bundleName'.\n";
    exit(1);
}

echo "Files to deploy for bundle '$bundleName':\n";
foreach ($filesToDeploy as $file) {
    echo " - $file\n";
}


$latestVersion = 0;

list($connection, $channel) = getDeployRabbit();
switch ($machineName) {
    case 'beDev':
        $queueName = 'beDevToDeploy';
        $responseQueue = 'deployToBeDev';
        break;
    case 'beQA':
        $queueName = 'beQAToDeploy';
        $responseQueue = 'deployToBeQA';
        break;
    case 'feDev':
        $queueName = 'feDevToDeploy';
        $responseQueue = 'deployToFeDev';
        break;
    case 'feQA':
        $queueName = 'feQAToDeploy';
        $responseQueue = 'deployToFeQA';
        break;
    case 'dmzDev':
        $queueName = 'dmzDevToDeploy';
        $responseQueue = 'deployToDmzDev';
        break;
    case 'dmzQA':
        $queueName = 'dmzQAToDeploy';
        $responseQueue = 'deployToDmzQA';
        break;
    default:
        echo "Invalid machine name '$machineName'.\n";
        exit(1);
}
// Declare the queue
$channel->queue_declare($queueName, false, true, false, false);
$data = json_encode([
    'bundle' => $bundleName
]);

// Create the message
$msg = new AMQPMessage($data, ['delivery_mode' => 2]);

// Send the message to the queue
$channel->basic_publish($responseMsg, 'directExchange', 'beDevToDeploy');
echo " [x] Sent '$bundleName'\n";
// Consume the 'deployToBeDev' queue and wait for the version number
$callback = function ($msg) use (&$latestVersion) {
    $latestVersion = $msg->body;
    echo " [x] Received version number: $latestVersion\n";
    $msg->ack();
};

$channel->basic_consume($responseQueue, '', false, false, false, false, $callback);

// Wait for the message
while ($channel->is_consuming()) {
    $channel->wait();
}

// Close the RabbitMQ connection
closeRabbit($connection, $channel);

// Setting the previous version and the new version for comparison later
$previousVersion = $latestVersion;
$newVersion = $latestVersion + 1;

// Debugging statements
echo "Latest Version: $latestVersion\n";
echo "Previous Version: $previousVersion\n";
echo "New Version: $newVersion\n";

$currentVersionPath = "$currentPath/{$bundleName}_$previousVersion";
$newVersionPath = "$currentPath/{$bundleName}_$newVersion";

echo "Current Version Path: $currentVersionPath\n";
echo "New Version Path: $newVersionPath\n";

// Moving the current version folder to the archive
if (is_dir($currentVersionPath)) {
    $archiveVersionPath = "$archivePath/{$bundleName}_$previousVersion";
    if (!rename($currentVersionPath, $archiveVersionPath)) {
        echo "Failed to move {$bundleName}_$previousVersion to archive.\n";
        exit(1);
    }
    echo "Moved {$bundleName}_$previousVersion to archive.\n";
} else {
    echo "No current version found for bundle '$bundleName'. Proceeding to create version $newVersion.\n";
}

// Ensure the new version directory exists
if (!is_dir($newVersionPath)) {
    if (!mkdir($newVersionPath, 0755, true)) {
        echo "Failed to create directory $newVersionPath\n";
        exit(1);
    }
}

// Function to copy files with directory structure
function copyFilesWithStructure($files, $sourceBasePath, $destinationBasePath) {
    foreach ($files as $relativePath) {
        $sourceFile = $sourceBasePath . $relativePath;
        $destinationFile = $destinationBasePath . $relativePath;

        // Ensure the destination directory exists
        $destinationDir = dirname($destinationFile);
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true)) {
                echo "Failed to create directory $destinationDir\n";
                continue;
            }
        }

        if (!copy($sourceFile, $destinationFile)) {
            echo "Failed to copy $sourceFile to $destinationFile\n";
        } else {
            echo "Copied $sourceFile to $destinationFile\n";
        }
    }
}

// Copying the files from sourcePath to the new version directory
copyFilesWithStructure($filesToDeploy, $sourcePath, $newVersionPath);
echo "Copied bundle '$bundleName' files to {$bundleName}_$newVersion in $newVersionPath\n";

// Comparing all files in the new version to the latest previous version in archive to create a changeLog.txt
if (isset($archiveVersionPath) && is_dir($archiveVersionPath)) {
    $changelogPath = "$newVersionPath/changeLog_{$newVersion}.txt";
    $differencesFound = false;

    foreach ($filesToDeploy as $relativePath) {
        $oldFile = $archiveVersionPath . $relativePath;
        $newFile = $newVersionPath . $relativePath;

        if (file_exists($oldFile) && file_exists($newFile)) {
            $command = "diff -u $oldFile $newFile";
            exec($command, $output, $returnVar);
            if ($returnVar !== 0) {
                $differencesFound = true;
                file_put_contents($changelogPath, implode("\n", $output) . "\n", FILE_APPEND);
            }
        } elseif (file_exists($newFile)) {
            $differencesFound = true;
            file_put_contents($changelogPath, "File added: $relativePath\n", FILE_APPEND);
        }
    }

    if ($differencesFound) {
        echo "Differences found, created a changelog: changeLog_{$newVersion}.txt\n";
    } else {
        echo "No differences found! No changeLog created.\n";
    }
} else {
    echo "No previous version found in archive to compare. Skipping changelog creation.\n";
}

// Adding functionality to send the currentVersion to the deployment machine using scp command
$deploymentUser = 'philzerin';
$deploymentHost = '172.29.82.171';
$deploymentPath = '/var/log/archive';

// Compress the file to be sent
$compressedFile = "{$bundleName}_{$newVersion}.zip";
$compressedFilePath = "$currentPath/$compressedFile";
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
