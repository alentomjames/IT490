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

function pullVersion() {}

function rollbackUpdate() {}




//getVersion("tested");
