<?php
session_start();
require "db.php";

if(!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: index.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"];
    $current_folder = isset($_POST["current_folder"]) && $_POST["current_folder"] !== "" ? (int)$_POST["current_folder"] : null;

    if(empty($current_folder) && isset($_POST["folder_id"]) && $_POST["folder_id"] !== "") {
        $current_folder = (int)$_POST["folder_id"];
    }

    // =============================================
    // ACTION: UPLOAD FILE (dengan Auto-Sort)
    // =============================================
    if($action == "upload") {
        if(isset($_FILES["file"]) && $_FILES["file"]["error"] == 0) {
            $file_name = basename($_FILES["file"]["name"]);
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $new_name = uniqid() . "." . $ext;
            $target_path = "uploads/" . $new_name;

            // Deteksi tipe file
            $image_exts = ['jpg','jpeg','png','gif','webp','bmp','heic','svg'];
            $video_exts = ['mp4','mov','avi','mkv','webm','flv','wmv','3gp'];
            $is_image = in_array($ext, $image_exts);
            $is_video = in_array($ext, $video_exts);

            // Auto-Sort: tentukan folder tujuan
            $target_folder_id = $current_folder; // default: folder saat ini

            // Hanya auto-sort jika upload dari root (tidak dalam folder tertentu)
            if($current_folder === null) {
                if($is_image) {
                    $auto_folder_name = "📷 Foto";
                    $auto_folder_icon = "foto";
                } elseif($is_video) {
                    $auto_folder_name = "🎬 Video";
                    $auto_folder_icon = "video";
                } else {
                    $auto_folder_name = "📄 Dokumen";
                    $auto_folder_icon = "dokumen";
                }

                // Cek apakah auto-folder sudah ada di root
                $check = $conn->prepare("SELECT id FROM folders WHERE name = ? AND parent_id IS NULL AND is_auto = 1");
                $check->bind_param("s", $auto_folder_name);
                $check->execute();
                $res = $check->get_result();

                if($res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $target_folder_id = $row['id'];
                } else {
                    // Buat auto-folder baru
                    $ins = $conn->prepare("INSERT INTO folders (parent_id, name, is_auto) VALUES (NULL, ?, 1)");
                    $ins->bind_param("s", $auto_folder_name);
                    $ins->execute();
                    $target_folder_id = $conn->insert_id;
                }
            }

            if(move_uploaded_file($_FILES["file"]["tmp_name"], $target_path)) {
                $stmt = $conn->prepare("INSERT INTO files (folder_id, file_name, file_path) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $target_folder_id, $file_name, $target_path);
                $stmt->execute();
                $_SESSION['toast'] = "✅ File berhasil diupload dan diatur otomatis!";
            }
        }
    // =============================================
    // ACTION: CREATE FOLDER
    // =============================================
    } elseif($action == "create_folder") {
        $folder_name = $_POST["folder_name"];
        $parent_id = isset($_POST["parent_id"]) && $_POST["parent_id"] !== "" ? (int)$_POST["parent_id"] : null;
        $stmt = $conn->prepare("INSERT INTO folders (parent_id, name, is_auto) VALUES (?, ?, 0)");
        $stmt->bind_param("is", $parent_id, $folder_name);
        $stmt->execute();
        $_SESSION['toast'] = "📁 Folder berhasil dibuat!";
    // =============================================
    // ACTION: DELETE FILE
    // =============================================
    } elseif($action == "delete_file") {
        $id = (int)$_POST["id"];
        $stmt = $conn->prepare("SELECT file_path FROM files WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if($row = $res->fetch_assoc()) {
            if(file_exists($row["file_path"])) {
                unlink($row["file_path"]);
            }
            $delstmt = $conn->prepare("DELETE FROM files WHERE id = ?");
            $delstmt->bind_param("i", $id);
            $delstmt->execute();
            $_SESSION['toast'] = "🗑️ File berhasil dihapus!";
        }
    // =============================================
    // ACTION: DELETE FOLDER
    // =============================================
    } elseif($action == "delete_folder") {
        $id = (int)$_POST["id"];
        $stmt = $conn->prepare("DELETE FROM folders WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['toast'] = "🗑️ Folder berhasil dihapus!";
    }

    $redirect_url = "dashboard.php";
    if($current_folder) {
        $redirect_url .= "?folder=" . $current_folder;
    }
    header("Location: " . $redirect_url);
    exit;
}
?>
