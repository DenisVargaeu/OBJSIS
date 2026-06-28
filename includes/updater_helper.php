<?php
// includes/updater_helper.php

class OBJSIS_Updater
{
  private $pdo;
  private $updateUrl = 'http://objsis_update.denisvarga.eu/latest.json';
  private $tempDir;
  private $backupDir;
  private $stateFile;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
    $this->tempDir = dirname(__DIR__) . '/temp_update';
    $this->backupDir = dirname(__DIR__) . '/backups';
    $this->stateFile = $this->tempDir . '/progress.json';
    $this->ensureDirs();
  }

  // ---------------------------------------------------------------
  // PUBLIC: checkLatest — reads latest.json, returns status
  // ---------------------------------------------------------------
  public function checkUpdate()
  {
    $response = $this->curlGet($this->updateUrl, 10);
    if ($response === false) {
      return ['success' => false, 'message' => 'Cannot reach update server at ' . $this->updateUrl];
    }
    $data = json_decode($response, true);
    if (!$data) {
      return ['success' => false, 'message' => 'Invalid update data from server.'];
    }
    $latestVersion = $data['version'] ?? '0.0.0';
    return [
      'success'        => true,
      'has_update'     => version_compare($latestVersion, OBJSIS_VERSION, '>'),
      'current_version'=> OBJSIS_VERSION,
      'latest_version' => $latestVersion,
      'notes'          => $data['notes'] ?? '',
      'url'            => $data['url'] ?? '',
      'sql_url'        => $data['sql'] ?? '',
    ];
  }

  // ---------------------------------------------------------------
  // PUBLIC: stepProgress — polled by the browser between AJAX calls
  // ---------------------------------------------------------------
  public function stepProgress()
  {
    if (!file_exists($this->stateFile)) {
      return ['status' => 'idle', 'step' => '', 'detail' => ''];
    }
    $raw = file_get_contents($this->stateFile);
    $s = json_decode($raw, true) ?: [];
    return [
      'status' => $s['status'] ?? 'running',
      'step'   => $s['step']   ?? '',
      'detail' => $s['detail'] ?? '',
    ];
  }

  // ---------------------------------------------------------------
  // PUBLIC: runStep — dispatches to the correct handler
  // step 1 = backup  2 = download  3 = extract  4 = sql  5 = cleanup
  // ---------------------------------------------------------------
  public function runStep($step, $updateData)
  {
    $this->setProgress('running', 'Backing up database…', '');
    switch ((int)$step) {
      case 1: return $this->stepBackup();
      case 2: return $this->stepDownload($updateData['url'] ?? '');
      case 3: return $this->stepExtract();
      case 4: return $this->stepSql($updateData['sql_url'] ?? '');
      case 5: return $this->stepCleanup();
      default: return ['success' => false, 'message' => 'Unknown step ' . $step];
    }
  }

  // ---------------------------------------------------------------
  // PRIVATE: step helpers
  // ---------------------------------------------------------------
  private function stepBackup()
  {
    $this->setProgress('running', 'Backing up database', '');
    $backupFile = $this->backupDir . '/db_backup_' . date('Y-m-d_H-i-s') . '.sql';
    try {
      $handle = fopen($backupFile, 'w');
      $tables = [];
      $result = $this->pdo->query('SHOW TABLES');
      while ($row = $result->fetch(PDO::FETCH_NUM)) $tables[] = $row[0];
      foreach ($tables as $table) {
        $res = $this->pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_ASSOC);
        fwrite($handle, "\n\n" . $res['Create Table'] . ";\n\n");
        $result = $this->pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
          $keys   = array_keys($row);
          $values = array_map([$this->pdo, 'quote'], array_values($row));
          fwrite($handle, 'INSERT INTO `' . implode('`, `', $keys) . '` VALUES (' . implode(', ', $values) . ');' . "\n");
        }
      }
      fclose($handle);
      $this->setProgress('running', 'Backup done', 'Database backed up → ' . basename($backupFile));
      return ['success' => true, 'step' => 1];
    } catch (Exception $e) {
      $this->setProgress('error', 'Backup failed', $e->getMessage());
      return ['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()];
    }
  }

  private function stepDownload($url)
  {
    if (empty($url)) {
      $this->setProgress('running', 'Download skipped', 'No ZIP URL provided, moving on.');
      return ['success' => true, 'step' => 2, 'skipped' => true];
    }
    $this->setProgress('running', 'Downloading update…', 'Fetching ZIP from GitHub…');
    $zipPath = $this->tempDir . '/update.zip';
    // Use direct file_get_contents fallback if curl fails
    $ok = false;
    if (function_exists('curl_version')) {
      $ok = $this->downloadCurl($url, $zipPath);
    }
    if (!$ok && ini_get('allow_url_fopen')) {
      $ok = $this->downloadFileGet($url, $zipPath);
    }
    if (!$ok) {
      $msg = 'Failed to download update ZIP from: ' . $url;
      $this->setProgress('error', 'Download failed', $msg);
      return ['success' => false, 'message' => $msg];
    }
    $size = filesize($zipPath);
    $this->setProgress('running', 'Download complete', number_format($size / 1024 / 1024, 2) . ' MB');
    return ['success' => true, 'step' => 2];
  }

  private function stepExtract()
  {
    $zipPath = $this->tempDir . '/update.zip';
    if (!file_exists($zipPath)) {
      $this->setProgress('running', 'Extract skipped', 'No ZIP to extract.');
      return ['success' => true, 'step' => 3, 'skipped' => true];
    }
    $this->setProgress('running', 'Extracting files', 'Applying updates to installation…');
    $zip = new ZipArchive;
    $openResult = $zip->open($zipPath);
    if ($openResult !== TRUE) {
      $msgs = [
        ZipArchive::ER_EXISTS   => 'File already exists',
        ZipArchive::ER_INCONS   => 'Zip archive inconsistent',
        ZipArchive::ER_INVAL    => 'Invalid argument',
        ZipArchive::ER_MEMORY   => 'Malloc failure',
        ZipArchive::ER_NOENT    => 'No such file',
        ZipArchive::ER_NOZIP    => 'Not a zip archive',
        ZipArchive::ER_OPEN     => 'Cannot open file',
      ];
      $msg = $msgs[$openResult] ?? "Unknown error code $openResult";
      $this->setProgress('error', 'Extract failed', $msg);
      return ['success' => false, 'message' => 'Failed to open ZIP archive: ' . $msg];
    }
    $root = dirname(__DIR__);
    $prefix = '';
    if ($zip->numFiles > 0) {
      $first = $zip->getNameIndex(0);
      $parts = explode('/', $first);
      if (count($parts) > 1 && $zip->locateName($parts[0] . '/') !== false) {
        $prefix = $parts[0] . '/';
      }
    }
    $count = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
      $filename = $zip->getNameIndex($i);
      $target = $prefix === '' || strpos($filename, $prefix) !== 0
        ? $filename : substr($filename, strlen($prefix));
      if (empty($target)) continue;
      if ($target === '.gitignore') continue;
      if (strpos($target, 'uploads/') === 0)     continue;
      if (strpos($target, 'logs/') === 0)        continue;
      if (strpos($target, '.vscode/') === 0)     continue;
      if (strpos($target, 'temp_update/') === 0)   continue;
      if (strpos($target, 'backups/') === 0)      continue;
      if ($target === 'config/db.php')            continue;

      $fullPath = rtrim($root, '/') . '/' . $target;
      $dir = dirname($fullPath);
      if (!is_dir($dir)) @mkdir($dir, 0755, true);
      if (substr($filename, -1) === '/') {
        if (!is_dir($fullPath)) @mkdir($fullPath, 0755, true);
      } else {
        $content = $zip->getFromName($filename);
        if ($content !== false) {
          file_put_contents($fullPath, $content);
          $count++;
        }
      }
    }
    $zip->close();
    @unlink($zipPath);
    $this->setProgress('running', 'Extract complete', number_format($count) . ' files updated');
    return ['success' => true, 'step' => 3];
  }

  private function stepSql($sqlUrl)
  {
    if (empty($sqlUrl)) {
      $this->setProgress('running', 'SQL skipped', 'No SQL migration URL.');
      return ['success' => true, 'step' => 4, 'skipped' => true];
    }
    $this->setProgress('running', 'Running database migration', 'Downloading SQL…');
    $sqlPath = $this->tempDir . '/update.sql';
    $got = false;
    if (function_exists('curl_version')) {
      $got = $this->downloadCurl($sqlUrl, $sqlPath);
    }
    if (!$got && ini_get('allow_url_fopen')) {
      $got = $this->downloadFileGet($sqlUrl, $sqlPath);
    }
    if (!$got || filesize($sqlPath) === 0) {
      @unlink($sqlPath);
      $this->setProgress('running', 'SQL skipped', 'Could not download SQL file (it may not exist yet — that is OK).');
      return ['success' => true, 'step' => 4, 'skipped' => true];
    }
    $this->setProgress('running', 'Running database migration', 'Applying SQL…');
    $sql = file_get_contents($sqlPath);
    $queries = array_filter(array_map('trim', explode(";\n", str_replace("\r\n", "\n", $sql))));
    $applied = 0;
    foreach ($queries as $query) {
      if ($query === '') continue;
      try {
        $this->pdo->exec($query);
        $applied++;
      } catch (Exception $e) {
        error_log('OBJSIS update SQL error: ' . $e->getMessage());
      }
    }
    @unlink($sqlPath);
    $this->setProgress('running', 'SQL complete', $applied . ' statements applied');
    return ['success' => true, 'step' => 4];
  }

  private function stepCleanup()
  {
    $this->cleanup();
    $this->setProgress('done', 'Update complete!', 'Refreshing…');
    return ['success' => true, 'step' => 5];
  }

  // ---------------------------------------------------------------
  // PRIVATE: download helpers
  // ---------------------------------------------------------------
  private function downloadCurl($url, $path)
  {
    $fp = fopen($path, 'w+');
    if (!$fp) return false;
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL            => $url,
      CURLOPT_RETURNTRANSFER => false,
      CURLOPT_FILE           => $fp,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_USERAGENT      => 'OBJSIS-Updater',
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_TIMEOUT        => 120,
      CURLOPT_BUFFERSIZE     => 65536,
      CURLOPT_NOPROGRESS     => false,
      CURLOPT_PROGRESSFUNCTION => function ($dlTotal, $dlNow, $ulTotal, $ulNow) use ($path) {
        if ($dlTotal > 0 && round($dlNow / 1024 / 1024, 1) !== round(($dlNow - 65536) / 1024 / 1024, 1)) {
          $this->setProgress('running', 'Downloading…', number_format($dlNow / 1024 / 1024, 2) . ' / ' . number_format($dlTotal / 1024 / 1024, 2) . ' MB');
        }
        return 0;
      },
    ]);
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    return $r && $code >= 200 && $code < 400 && @filesize($path) > 1024;
  }

  private function downloadFileGet($url, $path)
  {
    $ctx = stream_context_create([
      'http' => [
        'method'  => 'GET',
        'header'  => "User-Agent: OBJSIS-Updater\r\n",
        'timeout' => 120,
      ]
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data && strlen($data) > 1024) {
      file_put_contents($path, $data);
      return true;
    }
    @unlink($path);
    return false;
  }

  // ---------------------------------------------------------------
  // PRIVATE: helpers
  // ---------------------------------------------------------------
  private function ensureDirs()
  {
    if (!is_dir($this->tempDir))   @mkdir($this->tempDir,   0755, true);
    if (!is_dir($this->backupDir)) @mkdir($this->backupDir, 0755, true);
  }

  private function setProgress($status, $step, $detail)
  {
    @file_put_contents($this->stateFile, json_encode(compact('status', 'step', 'detail')));
  }

  private function cleanup()
  {
    if (is_dir($this->tempDir)) {
      $files = glob($this->tempDir . '/*');
      foreach ($files as $f) @unlink($f);
      @rmdir($this->tempDir);
    }
  }
}
