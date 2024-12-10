<?php

require_once './webserver/vendor/autoload.php';
require_once './db_connection.php'; // file has db connection
require_once './webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$db = getDbConnection();

function getVersion($bundleName)
{
    global $db;

    try {
        $query = "SELECT MAX(version_number) AS version_number FROM deployments WHERE bundle_name = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $bundleName);
        $stmt->execute();

        $versionNumber = null;
        $stmt->bind_result($versionNumber);
        $stmt->fetch();
        $stmt->close();

        if ($versionNumber === null) {
            $initialVersion = 1;
            $filePath = '';
            $status = 'new';

            $insertQuery = "INSERT INTO deployments (bundle_name, version_number, file_path, status) VALUES (?, ?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bind_param('siss', $bundleName, $initialVersion, $filePath, $status);
            $insertStmt->execute();

            $nextVersion = $initialVersion + 1;
            $insertQuery = "INSERT INTO deployments (bundle_name, version_number, file_path, status) VALUES (?, ?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bind_param('siss', $bundleName, $nextVersion, $filePath, $status);
            $insertStmt->execute();

            echo $initialVersion;
            echo $nextVersion;
            return $initialVersion;
        }

        $currentDir = "/var/log/current";
        $archiveDir = "/var/log/archive";

        $currentFilePath = "$currentDir/{$bundleName}_{$versionNumber}.zip";
        $archivedFilePath = "$archiveDir/{$bundleName}_{$versionNumber}.zip";

        if (file_exists($currentFilePath)) {
            rename($currentFilePath, $archivedFilePath);
            $nextVersion = $versionNumber + 1;
            echo "Archived current bundle: $currentFilePath to $archivedFilePath\n";
        } else {
            echo "Current bundle file $currentFilePath does not exist, skipping archive step.\n";
            return "File does not exist in current directory on Deployment Machine\n";
        }

        $filePath = '';
        $status = 'new';

        $insertQuery = "INSERT INTO deployments (bundle_name, version_number, file_path, status) VALUES (?, ?, ?, ?)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bind_param('siss', $bundleName, $nextVersion, $filePath, $status);
        $insertStmt->execute();

        echo $versionNumber . "\n";
        return $versionNumber;
    } catch (Exception $e) {
        echo $e->getMessage();
        return "error";
    }
}

function pullVersion($bundleName, $queueName)
{
    global $db;

    try {
        $targetMachine = '';
        $remotePath = '/var/log/current';
        switch ($queueName) {
            case 'deployToBeDev':
                $queueName = 'deployToBeDev';
                $targetMachine = 'ppetroski@172.29.4.30';
                break;
            case 'deployToBeQA':
                $queueName = 'deployToBeQA';
                $targetMachine = 'ppetroski@172.29.87.41';
                break;
            case 'deployToBeProd':
                $queueName = 'deployToBeProd';
                $targetMachine = ''; //FILL THIS IN WHEN READY
                break;
            case 'deployToFeDev':
                $queueName = 'deployToFeDev';
                $targetMachine = 'alen@172.29.137.82';
                break;
            case 'deployToFeQA':
                $queueName = 'deployToFeQA';
                $targetMachine = 'alen@172.29.87.169';
                break;
            case 'deployToFeProd':
                $queueName = 'deployToFeProd';
                $targetMachine = 'alen@172.29.244.237'; //FILL THIS IN WHEN READY
                break;
            case 'deployToDmzDev':
                $queueName = 'deployToDmzDev';
                $targetMachine = 'al643@172.29.2.108';
                break;
            case 'deployToDmzQA':
                $queueName = 'deployToDmzQA';
                $targetMachine = 'al643@172.29.63.70';
                break;
            case 'deployToDmzProd':
                $queueName = 'deployToDmzProd';
                $targetMachine = ''; //FILL THIS IN WHEN READY
                break;
            default:
                echo "Invalid queue name '$queueName'.\n";
                exit(1);
        }

        $query = "SELECT version_number FROM deployments WHERE bundle_name = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $bundleName);
        $stmt->execute();

        $versionNumber = null;
        $stmt->bind_result($versionNumber);
        $fetchResult = $stmt->fetch();
        $stmt->close();

        if (!$fetchResult) {
            return json_encode([
                'status' => 'fail',
                'message' => "No version found for bundle: $bundleName"
            ]);
        }

        $currentFilePath = "/var/log/current/{$bundleName}_{$versionNumber}.zip";

        if (!file_exists($currentFilePath)) {
            return json_encode([
                'status' => 'fail',
                'message' => "File not found for bundle: $bundleName, version: $versionNumber"
            ]);
        }

        $scpCommand = "scp -C $currentFilePath $targetMachine:$remotePath";
        exec($scpCommand, $output, $returnVar);

        if ($returnVar !== 0) {
            return json_encode([
                'status' => 'fail',
                'message' => "Failed to SCP file: $currentFilePath to $targetMachine:$remotePath",
                'output' => implode("\n", $output)
            ]);
        }

        return json_encode([
            'type' => 'sent',
            'status' => 'success',
            'message' => "File {$currentFilePath} sent to $targetMachine:$remotePath",
            'bundle' => $bundleName,
            'version' => $versionNumber,
            'target' => $targetMachine
        ]);
    } catch (Exception $e) {
        return json_encode([
            'status' => 'fail',
            'message' => 'Error during pullVersion',
            'error' => $e->getMessage()
        ]);
    }
}


function rollbackUpdate($bundleName, $previousVersion, $targetVMiP, $returnQueue, $user)
{
    $previousBundle = "/var/log/archive/{$bundleName}_{$previousVersion}.zip";
    $destinationPath = "/var/log/current";
    $scpCommand = "scp -C $previousBundle $user@$targetVMiP:$destinationPath";
    $returnVar = 0;
    exec($scpCommand, $output, $returnVar);

    if ($returnVar !== 0) {
        return json_encode([
            'status' => 'fail',
            'bundle' => $bundleName,
            'previous_version' => $previousVersion
        ]);
        echo "Failed to deploy previous version $previousVersion of $bundleName to $targetVMiP\n";
        throw new Exception("SCP command failed: " . implode("\n", $output));
    } else {
        return json_encode([
            'status' => 'sent',
            'bundle' => $bundleName,
            'previous_version' => $previousVersion
        ]);
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
        $query = "UPDATE deployments d1 JOIN (SELECT MAX(version_number) AS max_version FROM deployments WHERE bundle_name = ?) d2 ON d1.version_number = d2.max_version SET d1.status = ? WHERE d1.bundle_name = ?";
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
        $previousVersion = null;
        $stmt->bind_result($previousVersion);
        $stmt->fetch();
        echo "Rolling back to version $previousVersion\n";
        rollbackUpdate($bundleName, $previousVersion, $targetVMiP, $returnQueue, $user);
    }
    echo "Status updated successfully\n";
    return "Status updated successfully";
}




//getVersion("tested");
