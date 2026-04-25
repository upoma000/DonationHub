<?php
session_start();
include("db.php");

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
include("navbar.php");

$message     = "";
$messageType = "success";

// PHP upload error code → human readable message
function uploadErrorMessage($code) {
    $msgs = [
        UPLOAD_ERR_INI_SIZE   => "File exceeds server's max upload size (upload_max_filesize in php.ini).",
        UPLOAD_ERR_FORM_SIZE  => "File exceeds the form's MAX_FILE_SIZE.",
        UPLOAD_ERR_PARTIAL    => "File was only partially uploaded.",
        UPLOAD_ERR_NO_FILE    => "No file was selected.",
        UPLOAD_ERR_NO_TMP_DIR => "Server is missing a temporary upload folder.",
        UPLOAD_ERR_CANT_WRITE => "Server failed to write file to disk (tmp dir not writable).",
        UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the upload.",
    ];
    return $msgs[$code] ?? "Unknown upload error (code $code).";
}

if (isset($_POST['submit'])) {
    $title = trim($_POST['title']);
    $desc  = trim($_POST['desc']);
    $cat   = (int) $_POST['cat'];
    $uid   = (int) $user['UserID'];

    // ── Step 1: basic field validation
    if (empty($title) || empty($desc) || $cat <= 0) {
        $message     = "Please fill in all required fields.";
        $messageType = "danger";

    } else {
        // ── Step 2: handle optional image upload
        $safeName = ""; // empty = no image

        $fileProvided = isset($_FILES['img']) && $_FILES['img']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($fileProvided) {
            // A file was chosen — validate it
            if ($_FILES['img']['error'] !== UPLOAD_ERR_OK) {
                $message     = "Upload error: " . uploadErrorMessage($_FILES['img']['error']);
                $messageType = "danger";
            } elseif ($_FILES['img']['size'] === 0) {
                $message     = "Uploaded file is empty.";
                $messageType = "danger";
            } else {
                $imgInfo = @getimagesize($_FILES['img']['tmp_name']);
                if ($imgInfo === false) {
                    $message     = "The selected file is not a valid image. Please upload a JPG, PNG, GIF, or WEBP.";
                    $messageType = "danger";
                } else {
                    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "upload" . DIRECTORY_SEPARATOR;
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    if (!is_writable($uploadDir)) {
                        $message     = "Server error: upload/ directory is not writable. Please chmod 755 (or 777 on local dev).";
                        $messageType = "danger";
                    } else {
                        $ext = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
                        if (empty($ext)) {
                            $mimeToExt = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
                            $ext = $mimeToExt[image_type_to_mime_type($imgInfo[2])] ?? 'jpg';
                        }
                        $safeName = uniqid('img_', true) . '.' . $ext;
                        $destPath = $uploadDir . $safeName;
                        if (!move_uploaded_file($_FILES['img']['tmp_name'], $destPath)) {
                            $message     = "Server error: move_uploaded_file() failed. Check folder permissions.";
                            $messageType = "danger";
                            $safeName    = "";
                        }
                    }
                }
            }
        }

        // ── Step 3: save to DB if no errors so far
        if (empty($message)) {
            $stmt = $conn->prepare(
                "INSERT INTO DonationPost_T (UserID, CategoryID, Title, Description, Image)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("iisss", $uid, $cat, $title, $desc, $safeName);
            if ($stmt->execute()) {
                $message = "Your request has been submitted and is pending admin approval.";
            } else {
                if ($safeName) @unlink($uploadDir . $safeName);
                $message     = "Database error: failed to save post. Please try again.";
                $messageType = "danger";
            }
            $stmt->close();
        }
    }
}

$catRes = $conn->query("SELECT * FROM Category_T ORDER BY CategoryName");
?>

<div class="container mt-4 pb-5" style="max-width:700px">

    <div class="mb-4">
        <a href="user.php" style="color:#6b7280;font-size:.875rem;text-decoration:none">
            <i class="bi bi-arrow-left me-1"></i> Back to campaigns
        </a>
        <h2 style="font-family:'Syne',sans-serif;font-size:1.5rem;margin:.5rem 0 .25rem">
            Create Donation Request
        </h2>
        <p style="color:#6b7280;font-size:.875rem;margin:0">
            Submit your campaign for admin review. Approved posts will appear in the dashboard.
        </p>
    </div>

    <?php if ($message): ?>
        <div class="alert mb-4" style="border-radius:10px;border:none;font-size:.875rem;
             <?php echo $messageType==='success'
                 ? 'background:#dcfce7;color:#166534'
                 : 'background:#fee2e2;color:#991b1b'; ?>">
            <i class="bi bi-<?php echo $messageType==='success' ? 'check' : 'exclamation-triangle'; ?>-circle me-2"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card" style="border-radius:16px;border:1px solid #e5e0da;box-shadow:0 4px 16px rgba(0,0,0,.06)">
        <div class="card-body" style="padding:2rem">
            <form method="POST" enctype="multipart/form-data">

                <div class="mb-4">
                    <label class="form-label">
                        Campaign Title <span style="color:#e8533a">*</span>
                    </label>
                    <input type="text" name="title" class="form-control"
                           placeholder="e.g. Help fund medical treatment for Rahim"
                           required maxlength="255"
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        Description <span style="color:#e8533a">*</span>
                    </label>
                    <textarea name="desc" class="form-control" rows="5"
                              placeholder="Describe the situation, why donations are needed, and how the money will be used..."
                              required><?php echo isset($_POST['desc']) ? htmlspecialchars($_POST['desc']) : ''; ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        Category <span style="color:#e8533a">*</span>
                    </label>
                    <select name="cat" class="form-select" required>
                        <option value="">— Select a category —</option>
                        <?php while ($c = $catRes->fetch_assoc()): ?>
                            <option value="<?php echo (int)$c['CategoryID']; ?>"
                                <?php echo (isset($_POST['cat']) && $_POST['cat'] == $c['CategoryID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['CategoryName']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        Campaign Image <span style="color:#6b7280;font-size:.8rem;font-weight:400">(optional)</span>
                    </label>
                    <input type="file" name="img" class="form-control" accept="image/*">
                    <div style="font-size:.78rem;color:#6b7280;margin-top:.4rem">
                        Optional. Accepted: JPG, PNG, GIF, WEBP.
                    </div>
                </div>

                <div style="border-top:1px solid #e5e0da;padding-top:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap">
                    <button name="submit" class="btn btn-brand">
                        <i class="bi bi-send me-1"></i> Submit for Approval
                    </button>
                    <a href="user.php" class="btn btn-outline-secondary">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    <!-- Upload folder quick-fix instructions -->
    <div style="margin-top:1.25rem;background:#f8f5f2;border:1px solid #e5e0da;border-radius:10px;padding:1rem 1.25rem;font-size:.82rem;color:#6b7280">
        <strong style="color:#1a1a2e"><i class="bi bi-folder me-1"></i> Setup note:</strong>
        Make sure an <code>upload/</code> folder exists inside your project directory.
        On XAMPP/WAMP: right-click the folder → Properties → make sure it's not read-only.
        <br>On Linux/Mac: run <code>chmod 755 upload/</code>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>