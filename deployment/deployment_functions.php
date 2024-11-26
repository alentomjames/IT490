<?php
require_once './webserver/vendor/autoload.php';
require_once './db_connection.php'; // file has db connection
require_once './webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ

$db = getDbConnection();

function deployUpdate($targetVMiP, $bundlePackage)
{
    global $db;

    $bundleName = $bundlePackage['name'];
    $versionNumber = $bundlePackage['version'];
    $filePath = $bundlePackage['path'];
    $timestamp = time();

    try {
        $query = "SELECT version_number, status FROM deployments WHERE bundle_name = :bundle_name ORDER BY created_at DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':bundle_name', $bundleName);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

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
        $insertStmt->bindParam(':bundle_name', $bundleName);
        $insertStmt->bindParam(':version_number', $versionNumber);
        $insertStmt->bindParam(':file_path', $filePath);
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
