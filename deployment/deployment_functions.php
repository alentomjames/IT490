<?php

require_once './webserver/vendor/autoload.php';
require_once './db_connection.php'; // file has db connection
require_once './webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ

$db = getDbConnection();

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


function rollbackUpdate() {}




//getVersion("tested");
