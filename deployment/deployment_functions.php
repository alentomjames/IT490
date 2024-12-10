<?php

require_once './webserver/vendor/autoload.php';
require_once './db_connection.php'; // file has db connection
require_once './webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ

$db = getDbConnection();

use PhpAmqpLib\Message\AMQPMessage;

//deployUpdate: target IP and package info (json). Compares versions in try/catch block.
function storePackage($targetVMiP, $bundleName, $versionNumber, $filePath)
{
    global $db;

    // $bundleName = $packageInfo['name'];
    // $versionNumber = $packageInfo['version'];
    // $filePath = $packageInfo['path'];
    $timestamp = time();

    try {
        $query = "SELECT version_number, status FROM deployments WHERE bundle_name = :bundle_name ORDER BY created_at DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param(':bundle_name', $bundleName);
        $stmt->execute();
        $result = $stmt->fetch();

        $latestVersion = $result['version_number'] ?? null;
        $latestStatus = $result['status'] ?? 'new';

        if ($latestVersion && version_compare($versionNumber, $latestVersion, '<=')) { //version_compare returns -1 if lower, 0 equal, 1 if higher
            return json_encode([
                'status' => 'failure',
                'message' => "Version $versionNumber is not newer than the current version $latestVersion"
            ]);
        }

        if ($latestStatus === 'pass') {
            $archivePath = "/var/log/archive/{$bundleName}_{$latestVersion}.tar.gz";
            $currentPath = "/var/log/current/{$bundleName}";
            if (file_exists($currentPath)) {
                rename($currentPath, $archivePath);
            }
        }

        $currentPath = "/var/log/current/{$bundleName}";
        $scpCommand = "scp $filePath user@$targetVMiP:$currentPath";
        $output = [];
        $returnVar = 0;
        exec($scpCommand, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception("SCP command failed: " . implode("\n", $output));
        }

        $insertQuery = "INSERT INTO deployments (bundle_name, version_number, file_path, status)
                        VALUES (:bundle_name, :version_number, :file_path, 'new')";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bind_param(':bundle_name', $bundleName);
        $insertStmt->bind_param(':version_number', $versionNumber);
        $insertStmt->bind_param(':file_path', $filePath);
        $insertStmt->execute();

        return json_encode([
            'status' => 'success',
            'message' => "Successfully deployed version $versionNumber of $bundleName to $targetVMiP"
        ]);
    } catch (Exception $e) {
        return json_encode([
            'status' => 'failure',
            'message' => "Deployment failed",
            'error' => $e->getMessage()
        ]);
    }
}

function rollbackUpdate($bundleName, $previousVersion, $targetVMiP, $returnQueue, $user)
{
    list($connection, $channel) = getDeployRabbit();
    $channel->queue_declare($returnQueue, false, true, false, false);
    $previousBundle = "/var/log/archive/{$bundleName}_{$previousVersion}.zip";
    $destinationPath = "/var/log/current";
    $scpCommand = "scp -0 $previousBundle $user@$targetVMiP:$destinationPath";
    $returnVar = 0;
    exec($scpCommand, $output, $returnVar);

    if ($returnVar !== 0) {
        $failresponse = json_encode([
            'status' => 'fail',
            'bundle' => $bundleName,
            'previous_version' => $previousVersion
        ]);
        $msg = new AMQPMessage($failresponse, ['delivery_mode' => 2]);
        $channel->basic_publish($msg, 'directExchange', $returnQueue);
        echo "Failed to deploy previous version $previousVersion of $bundleName to $targetVMiP\n";
        throw new Exception("SCP command failed: " . implode("\n", $output));
    } else {
        $response = json_encode([
            'status' => 'sent',
            'bundle' => $bundleName,
            'previous_version' => $previousVersion
        ]);
        $msg = new AMQPMessage($response, ['delivery_mode' => 2]);
        $channel->basic_publish($msg, 'directExchange', $returnQueue);
        echo "Successfully deployed previous version $previousVersion of $bundleName to $targetVMiP\n";
    }

}

function updateStatus($data)
{
    global $db;
    $status = $data['status'];
    $bundleName = $data['bundle'];
    $returnQueue = $data['return_queue'];
    $targetVMiP = $data['target_vm'];
    $user = $data['user'];

    try {
        $query = "UPDATE deployments SET status = ? WHERE bundle_name = ? AND version_number = (SELECT MAX(version_number) FROM deployments WHERE bundle_name = ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param('sss', $status, $bundleName, $bundleName);
        $stmt->execute();
        echo "Status updated to $status for $bundleName\n";

    } catch (Exception $e) {
        echo "Error updating status: " . $e->getMessage();
        return "Error updating status: " . $e->getMessage();
    }
    if ($status === 'fail') {
        $query = "
        SELECT version_number
        FROM deployments
        WHERE bundle_name = ?
        AND status = 'pass'
        ORDER BY version_number DESC
        LIMIT 1
        ";
        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $bundleName);
        $stmt->execute();
        $stmt->bind_result($previousVersion);
        $stmt->fetch();
        echo "Rolling back to version $previousVersion\n";
        rollbackUpdate($bundleName, $previousVersion, $targetVMiP, $returnQueue, $user);
    }
    echo "Status updated successfully\n";
    return "Status updated successfully";
}
function getVersion($bundleName)
{
    global $db;

    try {
        $query = "SELECT version_number FROM deployments WHERE bundle_name = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $bundleName);
        $stmt->execute();

        $versionNumber = null;
        $stmt->bind_result($versionNumber);
        $fetchResult = $stmt->fetch();

        $currentDir = "/var/log/current";
        $archiveDir = "/var/log/archive";

        if ($fetchResult) {
            $stmt->close();

            $currentFilePath = "$currentDir/{$bundleName}_{$versionNumber}.zip";
            $archivedFilePath = "$archiveDir/{$bundleName}_{$versionNumber}.zip";

            if (file_exists($currentFilePath)) {
                rename($currentFilePath, $archivedFilePath);
                echo "Archived current bundle: $currentFilePath to $archivedFilePath\n";
            } else {
                echo "Current bundle file $currentFilePath does not exist, skipping archive step.\n";
                return "File does not exist in current directory on Deployment Machine\n";
            }

            $nextVersion = $versionNumber + 1;

            $filePath = '';
            $status = 'new';

            $insertQuery = "INSERT INTO deployments (bundle_name, version_number, file_path, status) VALUES (?, ?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bind_param('siss', $bundleName, $nextVersion, $filePath, $status);
            $insertStmt->execute();

            echo $versionNumber;
            return $versionNumber;
        } else {
            $initialVersion = 0;
            $filePath = '';
            $status = 'new';

            $insertQuery = "INSERT INTO deployments (bundle_name, version_number, file_path, status) VALUES (?, ?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bind_param('siss', $bundleName, $initialVersion, $filePath, $status);
            $insertStmt->execute();

            echo "new";
            //return $initialVersion;
            return "new";
        }
    } catch (Exception $e) {
        echo $e->getMessage();
        return "error";
    }
}


getVersion("tested");

//
