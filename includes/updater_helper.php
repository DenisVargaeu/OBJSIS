<?php
// includes/updater_helper.php

class OBJSIS_Updater
{
    private $pdo;
    private $updateUrl = 'https://denisvarga.eu/objsis_update/latest.json'; //
    private $tempDir;
    private $backupDir;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->tempDir = dirname(__DIR__) . '/temp_update';
        $this->backupDir = dirname(__DIR__) . '/backups';
    }

    public function checkUpdate()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->updateUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'OBJSIS-Updater');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'message' => 'Connection failed: ' . $error];
        }

        $data = json_decode($response, true);
        if (!$data) {
            return ['success' => false, 'message' => 'Invalid update data from server.'];
        }

        $latestVersion = $data['version'];
        $hasUpdate = version_compare($latestVersion, OBJSIS_VERSION, '>');

        return [
            'success' => true,
            'has_update' => $hasUpdate,
            'current_version' => OBJSIS_VERSION,
            'latest_version' => $latestVersion,
            'notes' => $data['notes'] ?? '',
            'url' => $data['url'] ?? '',
            'sql_url' => $data['sql'] ?? ''
        ];
    }

    public function startUpdate($updateData)
    {
        try {
            // 1. Create Directories
            if (!is_dir($this->tempDir))
                mkdir($this->tempDir, 0755, true);
            if (!is_dir($this->backupDir))
                mkdir($this->backupDir, 0755, true);

            // 2. Backup Database
            $this->backupDatabase();

            // 3. Download ZIP
            $zipPath = $this->tempDir . '/update.zip';
            if (!$this->downloadFile($updateData['url'], $zipPath)) {
                throw new Exception("Failed to download update ZIP.");
            }

            // 4. Extract and Overwrite
            $this->extractUpdate($zipPath);

            // 5. Run SQL Migration (if any)
            if (!empty($updateData['sql_url'])) {
                $sqlPath = $this->tempDir . '/update.sql';
                if ($this->downloadFile($updateData['sql_url'], $sqlPath)) {
                    $this->runSqlFile($sqlPath);
                }
            }

            // 6. Finalize Version (if local file was replaced, config/version.php should now have new version)
            // If the zip didn't contain version.php, we might need to manually update it here?
            // Usually, the zip should include the new version.php.

            // 7. Cleanup
            $this->cleanup();

            return ['success' => true, 'message' => 'System updated successfully to version ' . $updateData['version']];

        } catch (Exception $e) {
            $this->cleanup();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function downloadFile($url, $path)
    {
        $fp = fopen($path, 'w+');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'OBJSIS-Updater');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $success = curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        return $success !== false;
    }

    private function extractUpdate($zipPath)
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === TRUE) {
            $root = dirname(__DIR__);
            $prefix = '';

            // 1. Identify common prefix (GitHub often wraps everything in a folder)
            if ($zip->numFiles > 0) {
                $firstFile = $zip->getNameIndex(0);
                $parts = explode('/', $firstFile);
                if (count($parts) > 1 && $zip->locateName($parts[0] . '/') !== false) {
                    $prefix = $parts[0] . '/';
                }
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                // Strip prefix if exists
                $targetFile = $filename;
                if ($prefix !== '' && strpos($filename, $prefix) === 0) {
                    $targetFile = substr($filename, strlen($prefix));
                }

                if (empty($targetFile))
                    continue;

                // Skip sensitive/user directories
                if (strpos($targetFile, 'config/db.php') !== false)
                    continue;
                if (strpos($targetFile, 'uploads/') === 0)
                    continue;
                if (strpos($targetFile, 'logs/') === 0)
                    continue;
                if (strpos($targetFile, '.vscode/') === 0)
                    continue;
                if ($targetFile === '.gitignore' || $targetFile === '.git/')
                    continue;

                // Ensure parent directory exists for the target file
                $fullPath = $root . '/' . $targetFile;
                $dir = dirname($fullPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                // If it's a directory entry in zip, don't try to "extract" as file
                if (substr($filename, -1) === '/') {
                    if (!is_dir($fullPath))
                        mkdir($fullPath, 0755, true);
                    continue;
                }

                file_put_contents($fullPath, $zip->getFromName($filename));
            }
            $zip->close();
        } else {
            throw new Exception("Failed to open ZipArchive.");
        }
    }

    private function backupDatabase()
    {
        $backupFile = $this->backupDir . '/db_backup_' . date('Y-m-d_H-i-s') . '.sql';

        try {
            $handle = fopen($backupFile, 'w');
            $tables = [];
            $result = $this->pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            foreach ($tables as $table) {
                // Get Create Table status
                $res = $this->pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
                fwrite($handle, "\n\n" . $res['Create Table'] . ";\n\n");

                // Get Data
                $result = $this->pdo->query("SELECT * FROM `$table` ");
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $keys = array_keys($row);
                    $values = array_values($row);
                    $values = array_map(function ($v) {
                        return $this->pdo->quote($v);
                    }, $values);
                    fwrite($handle, "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $values) . ");\n");
                }
            }
            fclose($handle);
        } catch (Exception $e) {
            // Silently fail backup if it's not critical
        }
    }

    private function runSqlFile($path)
    {
        $sql = file_get_contents($path);
        if ($sql) {
            $queries = explode(";", $sql);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $this->pdo->exec($query);
                }
            }
        }
    }

    private function cleanup()
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file))
                    unlink($file);
            }
            rmdir($this->tempDir);
        }
    }
}
?>