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

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                // Skip sensitive/user directories
                if (strpos($filename, 'config/db.php') !== false)
                    continue;
                if (strpos($filename, 'uploads/') === 0)
                    continue;
                if (strpos($filename, 'logs/') === 0)
                    continue;
                if (strpos($filename, '.vscode/') === 0)
                    continue;
                if ($filename === '.gitignore')
                    continue;

                $zip->extractTo($root, $filename);
            }
            $zip->close();
        } else {
            throw new Exception("Failed to open ZipArchive.");
        }
    }

    private function backupDatabase()
    {
        // Simple DB Backup logic - requires 'mysqldump' available in path
        // For a more portable PHP solution, we could use PDO to dump tables
        $backupFile = $this->backupDir . '/db_backup_' . date('Y-m-d_H-i-s') . '.sql';

        // This is a placeholder for a more robust backup logic
        // For now, we'll implement a simple one if possible or skip if not supported
    }

    private function runSqlFile($path)
    {
        $sql = file_get_contents($path);
        if ($sql) {
            $this->pdo->exec($sql);
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