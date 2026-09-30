<?php
session_start();
require "db.php";
if(!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true){
  header("Location: index.php"); exit;
}

$current_folder_id = isset($_GET["folder"]) && $_GET["folder"] !== "" ? (int)$_GET["folder"] : null;

// Get folders
if($current_folder_id){
  $fq = $conn->prepare("SELECT * FROM folders WHERE parent_id = ? ORDER BY name ASC");
  $fq->bind_param("i", $current_folder_id);
} else {
  $fq = $conn->prepare("SELECT * FROM folders WHERE parent_id IS NULL ORDER BY name ASC");
}
$fq->execute(); $folders = $fq->get_result();

// Get files
if($current_folder_id){
  $fiq = $conn->prepare("SELECT * FROM files WHERE folder_id = ? ORDER BY created_at DESC");
  $fiq->bind_param("i", $current_folder_id);
} else {
  $fiq = $conn->prepare("SELECT * FROM files WHERE folder_id IS NULL ORDER BY created_at DESC");
}
$fiq->execute(); $files = $fiq->get_result();

// Parent folder
$parent_id = null; $current_folder_name = "Semua File";
if($current_folder_id){
  $stmt = $conn->prepare("SELECT parent_id, name FROM folders WHERE id = ?");
  $stmt->bind_param("i", $current_folder_id);
  $stmt->execute(); $res = $stmt->get_result();
  if($row = $res->fetch_assoc()){
    $parent_id = $row["parent_id"];
    $current_folder_name = $row["name"];
  }
}

// Stats
$total_files = $conn->query("SELECT COUNT(*) as c FROM files")->fetch_assoc()["c"];
$total_folders = $conn->query("SELECT COUNT(*) as c FROM folders")->fetch_assoc()["c"];
$recent = $conn->query("SELECT COUNT(*) as c FROM files WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()["c"];
$total_size_mb = 0;
$size_res = $conn->query("SELECT file_path FROM files");
while($r = $size_res->fetch_assoc()){
  if(file_exists($r["file_path"])) $total_size_mb += filesize($r["file_path"]);
}
$total_size_mb = round($total_size_mb / 1048576, 1);

$recent_files = $conn->query("SELECT * FROM files ORDER BY created_at DESC LIMIT 5");

$usageBreakdown = [
  ["label" => "Images", "value" => 0, "percent" => 0, "class" => "purple"],
  ["label" => "Videos", "value" => 0, "percent" => 0, "class" => "green"],
  ["label" => "Documents", "value" => 0, "percent" => 0, "class" => "blue"],
  ["label" => "Other", "value" => 0, "percent" => 0, "class" => "amber"]
];
$file_type_res = $conn->query("SELECT file_name FROM files");
while($typeRow = $file_type_res->fetch_assoc()){
  $ext = strtolower(pathinfo($typeRow["file_name"], PATHINFO_EXTENSION));
  if(in_array($ext, ['jpg','jpeg','png','gif','webp','bmp'])){
    $usageBreakdown[0]["value"]++;
  } elseif(in_array($ext, ['mp4','mov','avi','mkv','webm'])){
    $usageBreakdown[1]["value"]++;
  } elseif($ext !== ''){
    $usageBreakdown[2]["value"]++;
  } else {
    $usageBreakdown[3]["value"]++;
  }
}
$total_usage = max(1, array_sum(array_column($usageBreakdown, "value")));
foreach($usageBreakdown as &$usage){
  $usage["percent"] = round(($usage["value"] / $total_usage) * 100);
}
unset($usage);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – Kayto Drive</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">

  <aside class="sidebar">
    <div class="sidebar-logo">
      <h2>Kayto<span> Drive</span></h2>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item <?= !$current_folder_id ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Dashboard
      </a>
      <a href="dashboard.php" class="nav-item">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
        Semua File
      </a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION["username"], 0, 1)) ?></div>
        <div>
          <div class="user-name"><?= htmlspecialchars($_SESSION["username"]) ?></div>
          <div class="user-role">Pemilik</div>
        </div>
      </div>
      <a href="logout.php" class="btn-logout">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Keluar
      </a>
    </div>
  </aside>

  <div class="main-content">
    <div class="topbar">
      <div class="breadcrumb">
        <a href="dashboard.php">Kayto Drive</a>
        <?php if($current_folder_id): ?>
          <span>›</span>
          <span><?= htmlspecialchars($current_folder_name) ?></span>
        <?php endif; ?>
      </div>
      <div class="topbar-actions">
        <button type="button" class="topbar-button">This week</button>
      </div>
    </div>

    <div class="page-body">
      <div class="overview-header">
        <div>
          <span class="eyebrow">Overview</span>
          <h1>Drive Dashboard</h1>
        </div>
        <div class="overview-actions">
          <button type="button" class="ghost-button">Filter</button>
          <a href="#upload" class="primary-button">Upload file</a>
        </div>
      </div>

      <div class="kpi-grid">
        <div class="kpi-card accent-blue">
          <div class="kpi-icon blue">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          </div>
          <div class="kpi-label">Total File</div>
          <div class="kpi-value"><?= $total_files ?></div>
          <div class="kpi-sub">File tersimpan</div>
        </div>
        <div class="kpi-card accent-yellow">
          <div class="kpi-icon yellow">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
          </div>
          <div class="kpi-label">Total Folder</div>
          <div class="kpi-value"><?= $total_folders ?></div>
          <div class="kpi-sub">Folder dibuat</div>
        </div>
        <div class="kpi-card accent-green">
          <div class="kpi-icon green">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
          </div>
          <div class="kpi-label">Upload Baru</div>
          <div class="kpi-value"><?= $recent ?></div>
          <div class="kpi-sub">7 hari terakhir</div>
        </div>
        <div class="kpi-card accent-purple">
          <div class="kpi-icon purple">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
          </div>
          <div class="kpi-label">Ukuran</div>
          <div class="kpi-value"><?= $total_size_mb ?></div>
          <div class="kpi-sub">MB dipakai</div>
        </div>
      </div>

      <div class="content-grid">
        <div class="panel panel-large">
          <div class="panel-header">
            <div>
              <p class="panel-kicker">Recent activity</p>
              <h2>File terbaru</h2>
            </div>
            <a href="dashboard.php">Lihat semua</a>
          </div>
          <div class="table-wrap">
            <table class="activity-table">
              <thead>
                <tr>
                  <th>Nama file</th>
                  <th>Tanggal</th>
                  <th>Ukuran</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if($recent_files && $recent_files->num_rows > 0): ?>
                  <?php while($item = $recent_files->fetch_assoc()):
                    $ext = strtolower(pathinfo($item['file_name'], PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']);
                    $isVideo = in_array($ext, ['mp4','mov','avi','mkv','webm']);
                    $fileSize = file_exists($item['file_path']) ? round(filesize($item['file_path']) / 1024, 1) . ' KB' : 'N/A';
                    $status = $isImage ? 'Image' : ($isVideo ? 'Video' : 'Document');
                  ?>
                    <tr>
                      <td class="file-cell">
                        <span class="file-bullet <?= $isImage ? 'purple' : ($isVideo ? 'green' : 'blue') ?>"><?= strtoupper(substr($ext ?: 'F', 0, 2)) ?></span>
                        <div>
                          <strong><?= htmlspecialchars($item['file_name']) ?></strong>
                          <small><?= htmlspecialchars($item['file_path']) ?></small>
                        </div>
                      </td>
                      <td><?= date('d M Y', strtotime($item['created_at'])) ?></td>
                      <td><?= $fileSize ?></td>
                      <td><span class="status-chip <?= $isImage ? 'purple' : ($isVideo ? 'green' : 'blue') ?>"><?= $status ?></span></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="4" class="empty-row">Belum ada file yang diupload.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="panel panel-side">
          <div class="panel-header compact">
            <div>
              <p class="panel-kicker">Storage</p>
              <h2>Overview</h2>
            </div>
          </div>
          <div class="usage-summary">
            <div class="usage-number"><?= $total_size_mb ?><span>MB</span></div>
            <p>Digunakan dari total kapasitas yang tersedia.</p>
          </div>
          <div class="usage-list">
            <?php foreach($usageBreakdown as $usage): ?>
              <div class="usage-row">
                <div class="usage-label">
                  <span class="dot <?= $usage['class'] ?>"></span>
                  <?= $usage['label'] ?>
                </div>
                <span><?= $usage['value'] ?></span>
              </div>
              <div class="progress">
                <span class="progress-bar <?= $usage['class'] ?>" style="width: <?= $usage['percent'] ?>%"></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="action-panel" id="upload">
        <form action="action.php" method="POST" enctype="multipart/form-data" id="uploadForm">
          <input type="hidden" name="action" value="upload">
          <input type="hidden" name="folder_id" value="<?= $current_folder_id ?>">
          <input type="file" name="file" id="fileInput" accept="image/*,video/*" onchange="submitUpload()">
          <label for="fileInput" class="upload-label">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            Upload File
          </label>
        </form>
        <div class="upload-progress" id="uploadProgress">
          <div class="spinner"></div>
          <span>Mengupload...</span>
        </div>
        <form action="action.php" method="POST" class="folder-form-inline">
          <input type="hidden" name="action" value="create_folder">
          <input type="hidden" name="parent_id" value="<?= $current_folder_id ?>">
          <input type="text" name="folder_name" placeholder="Nama folder baru...">
          <button type="submit" class="btn-action">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Buat Folder
          </button>
        </form>
      </div>

      <div class="file-panel">
        <div class="file-panel-header">
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="file-panel-title"><?= htmlspecialchars($current_folder_name) ?></span>
            <span class="file-count"><?= $folders->num_rows + $files->num_rows ?> item</span>
          </div>
        </div>

        <?php if($current_folder_id): ?>
          <a href="dashboard.php<?= $parent_id ? '?folder='.$parent_id : '' ?>" class="back-nav">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke atas
          </a>
        <?php endif; ?>

        <?php 
        $folders->data_seek(0);
        while($f = $folders->fetch_assoc()): ?>
          <div class="file-item">
            <div class="file-icon folder-icon">📁</div>
            <div class="file-info">
              <a href="dashboard.php?folder=<?= $f['id'] ?>" class="file-name">
                <?= htmlspecialchars($f['name']) ?>
              </a>
              <div class="file-meta">Folder · <?= date('d M Y', strtotime($f['created_at'])) ?></div>
            </div>
            <span class="badge folder">Folder</span>
            <div class="file-actions">
              <form action="action.php" method="POST" onsubmit="return confirm('Hapus folder ini?');" style="display:inline">
                <input type="hidden" name="action" value="delete_folder">
                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                <input type="hidden" name="current_folder" value="<?= $current_folder_id ?>">
                <button type="submit" class="btn-icon red" title="Hapus">
                  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </form>
            </div>
          </div>
        <?php endwhile; ?>

        <?php 
        $files->data_seek(0);
        while($f = $files->fetch_assoc()):
          $ext = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
          $is_image = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']);
          $is_video = in_array($ext, ['mp4','mov','avi','mkv','webm']);
          $file_url = "http://" . $_SERVER['HTTP_HOST'] . "/kayto_drive/" . $f['file_path'];
          $badge_class = $is_image ? 'image' : ($is_video ? 'video' : 'file');
          $badge_label = $is_image ? 'Gambar' : ($is_video ? 'Video' : 'File');
          $size = file_exists($f['file_path']) ? round(filesize($f['file_path'])/1024, 1).' KB' : '';
        ?>
          <div class="file-item">
            <div class="file-icon img-icon">
              <?php if($is_image): ?>
                <img src="<?= htmlspecialchars($f['file_path']) ?>" alt="">
              <?php else: ?>
                📄
              <?php endif; ?>
            </div>
            <div class="file-info">
              <a href="<?= htmlspecialchars($f['file_path']) ?>" target="_blank" class="file-name">
                <?= htmlspecialchars($f['file_name']) ?>
              </a>
              <div class="file-meta"><?= $size ?> · <?= date('d M Y, H:i', strtotime($f['created_at'])) ?></div>
            </div>
            <span class="badge <?= $badge_class ?>"><?= $badge_label ?></span>
            <div class="file-actions">
              <button class="btn-icon blue" title="Share" onclick="openShare('<?= addslashes($file_url) ?>', '<?= addslashes($f['file_name']) ?>')">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
              </button>
              <form action="action.php" method="POST" onsubmit="return confirm('Hapus file ini?');" style="display:inline">
                <input type="hidden" name="action" value="delete_file">
                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                <input type="hidden" name="current_folder" value="<?= $current_folder_id ?>">
                <button type="submit" class="btn-icon red" title="Hapus">
                  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </form>
            </div>
          </div>
        <?php endwhile; ?>

        <?php if($folders->num_rows == 0 && $files->num_rows == 0): ?>
          <div class="empty-state">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            <p>Belum ada file di sini. Upload file atau buat folder baru!</p>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<div class="modal-overlay" id="shareModal">
  <div class="modal">
    <h3>Bagikan File</h3>
    <p id="shareFileName">Pilih platform untuk berbagi</p>
    <div class="share-grid">
      <a id="shareWA" href="#" class="share-btn wa" target="_blank">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        WhatsApp
      </a>
      <a id="shareTG" href="#" class="share-btn tg" target="_blank">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
        Telegram
      </a>
      <a id="shareFB" href="#" class="share-btn fb" target="_blank">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        Facebook
      </a>
      <button id="shareCopy" class="share-btn copy" onclick="copyLink()">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        Salin Link
      </button>
    </div>
    <div class="copy-link">
      <input type="text" id="shareLinkInput" readonly>
    </div>
    <button class="btn-modal-close" onclick="closeShare()">Tutup</button>
  </div>
</div>

<script>
function submitUpload() {
  document.getElementById('uploadProgress').classList.add('active');
  document.getElementById('uploadForm').submit();
}
function openShare(url, name) {
  document.getElementById('shareFileName').textContent = name;
  document.getElementById('shareLinkInput').value = url;
  const enc = encodeURIComponent(url);
  const txt = encodeURIComponent('Lihat file ini: ' + name + ' ' + url);
  document.getElementById('shareWA').href = 'https://wa.me/?text=' + txt;
  document.getElementById('shareTG').href = 'https://t.me/share/url?url=' + enc + '&text=' + encodeURIComponent('Lihat file: ' + name);
  document.getElementById('shareFB').href = 'https://www.facebook.com/sharer/sharer.php?u=' + enc;
  document.getElementById('shareModal').classList.add('active');
}
function closeShare() {
  document.getElementById('shareModal').classList.remove('active');
}
function copyLink() {
  const input = document.getElementById('shareLinkInput');
  input.select();
  document.execCommand('copy');
  document.getElementById('shareCopy').textContent = '✓ Tersalin!';
  setTimeout(() => { document.getElementById('shareCopy').innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg> Salin Link'; }, 2000);
}
document.getElementById('shareModal').addEventListener('click', function(e) {
  if(e.target === this) closeShare();
});
</script>
</body>
</html>
