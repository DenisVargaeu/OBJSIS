<?php
// includes/addon_helper.php

class OBJSIS_AddonManager
{
    private $pdo;
    private $addonsDir;
    private $tempDir;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->addonsDir = dirname(__DIR__) . '/addons';
        $this->tempDir = dirname(__DIR__) . '/temp_addons';
        
        if (!is_dir($this->addonsDir)) {
            mkdir($this->addonsDir, 0755, true);
        }
    }

    /**
     * Scan the addons directory for valid addon folders
     */
    public function getAvailableAddons()
    {
        $addons = [];
        $folders = glob($this->addonsDir . '/*', GLOB_ONLYDIR);

        foreach ($folders as $folder) {
            $addonId = basename($folder);
            $jsonPath = $folder . '/addon.json';

            if (file_exists($jsonPath)) {
                $content = file_get_contents($jsonPath);
                $meta = json_decode($content, true);
                if ($meta) {
                    $meta['addon_id'] = $addonId;
                    $meta['status'] = $this->getAddonStatus($addonId);
                    $meta['has_ui'] = file_exists($folder . '/index.php');
                    $addons[] = $meta;
                }
            }
        }
        return $addons;
    }

    /**
     * Get addon status from database
     */
    private function getAddonStatus($addonId)
    {
        $stmt = $this->pdo->prepare("SELECT is_enabled, version FROM addons WHERE addon_id = ?");
        $stmt->execute([$addonId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'installed' => (bool)$row,
            'enabled' => $row ? (bool)$row['is_enabled'] : false,
            'installed_version' => $row ? $row['version'] : null
        ];
    }

    /**
     * Toggle addon state
     */
    public function toggleAddon($addonId, $enable)
    {
        $stmt = $this->pdo->prepare("UPDATE addons SET is_enabled = ? WHERE addon_id = ?");
        return $stmt->execute([$enable ? 1 : 0, $addonId]);
    }

    /**
     * Install or update an addon from a local path (after upload or download)
     */
    public function installAddon($addonId, $meta)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO addons (addon_id, name, version, description, author, is_enabled)
            VALUES (?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                version = VALUES(version),
                description = VALUES(description),
                author = VALUES(author)
        ");
        
        $success = $stmt->execute([
            $addonId,
            $meta['name'] ?? $addonId,
            $meta['version'] ?? '1.0.0',
            $meta['description'] ?? '',
            $meta['author'] ?? 'Unknown',
        ]);

        // Run SQL migration if exists
        $sqlPath = $this->addonsDir . '/' . $addonId . '/install.sql';
        if (file_exists($sqlPath)) {
            $this->runSqlFile($sqlPath);
        }

        return $success;
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
}
?>
