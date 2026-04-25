<?php
session_start();
include("db.php");

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
include("navbar.php");

// Category filter
$filterCat = isset($_GET['cat']) && is_numeric($_GET['cat']) ? (int)$_GET['cat'] : 0;

// Build query with optional category filter
$where = "WHERE p.Status = 'approved'";
if ($filterCat > 0) {
    $where .= " AND p.CategoryID = $filterCat";
}

$sql = "
    SELECT p.*, c.CategoryName,
           IFNULL(SUM(d.Amount), 0) AS TotalAmount,
           COUNT(d.DonationID)      AS DonorCount
    FROM DonationPost_T p
    JOIN Category_T c  ON p.CategoryID = c.CategoryID
    LEFT JOIN Donation_T d ON p.PostID = d.PostID
    $where
    GROUP BY p.PostID
    ORDER BY p.PostID DESC
";
$result = $conn->query($sql);

// All categories for filter dropdown
$cats = $conn->query("SELECT * FROM Category_T ORDER BY CategoryName");

// Quick stats
$totalPosts    = $conn->query("SELECT COUNT(*) AS n FROM DonationPost_T WHERE Status='approved'")->fetch_assoc()['n'];
$totalDonated  = $conn->query("SELECT IFNULL(SUM(Amount),0) AS s FROM Donation_T")->fetch_assoc()['s'];
?>

<!-- Stats bar -->
<div style="background:#fff; border-bottom:1px solid #e5e0da; padding:.75rem 0;">
    <div class="container d-flex gap-4 flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <span style="font-size:.8rem;color:#6b7280">Active campaigns:</span>
            <strong style="font-family:'Syne',sans-serif; color:#e8533a"><?php echo $totalPosts; ?></strong>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span style="font-size:.8rem;color:#6b7280">Total raised:</span>
            <strong style="font-family:'Syne',sans-serif; color:#16a34a">৳<?php echo number_format($totalDonated, 0); ?></strong>
        </div>
    </div>
</div>

<div class="container mt-4 pb-5">

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h2 style="font-family:'Syne',sans-serif;font-size:1.5rem;margin:0">
                <i class="bi bi-heart-fill me-2" style="color:#e8533a"></i>Donation Campaigns
            </h2>
            <p style="color:#6b7280;font-size:.875rem;margin:.25rem 0 0">
                Support a cause. Every amount helps.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <!-- Category filter -->
            <form method="GET" class="d-flex gap-2">
                <select name="cat" class="form-select form-select-sm" style="min-width:160px" onchange="this.form.submit()">
                    <option value="0">All Categories</option>
                    <?php
                    $cats->data_seek(0);
                    while ($c = $cats->fetch_assoc()):
                    ?>
                    <option value="<?php echo $c['CategoryID']; ?>"
                        <?php echo ($filterCat == $c['CategoryID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['CategoryName']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <?php if ($filterCat > 0): ?>
                    <a href="user.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </form>
            <a href="create.php" class="btn btn-brand btn-sm">
                <i class="bi bi-plus-lg me-1"></i> New Request
            </a>
        </div>
    </div>

    <!-- Cards grid -->
    <?php if ($result->num_rows === 0): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No campaigns found</h5>
            <p>
                <?php echo $filterCat > 0 ? 'No approved campaigns in this category yet.' : 'No approved campaigns yet.'; ?>
            </p>
            <a href="create.php" class="btn btn-brand">Be the first to create one</a>
        </div>
    <?php else: ?>
    <div class="row g-4">
        <?php while ($row = $result->fetch_assoc()):
            // Fake goal for progress display: 10x total or at least 5000
            $goal    = max((float)$row['TotalAmount'] * 1.5, 5000);
            $percent = min(100, round(($row['TotalAmount'] / $goal) * 100));
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <!-- Image -->
                <div style="position:relative;overflow:hidden;height:200px;border-radius:14px 14px 0 0;">
                    <?php if ($row['Image']): ?>
                        <img src="upload/<?php echo htmlspecialchars($row['Image']); ?>"
                             alt="<?php echo htmlspecialchars($row['Title']); ?>"
                             style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <div style="width:100%;height:100%;background:linear-gradient(135deg,#1a1a2e,#2d2d4e);display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-image" style="font-size:3rem;color:rgba(255,255,255,.3)"></i>
                        </div>
                    <?php endif; ?>
                    <!-- Category pill -->
                    <span style="position:absolute;top:12px;left:12px;background:rgba(0,0,0,.55);color:#fff;
                                 font-size:.7rem;padding:.25rem .6rem;border-radius:99px;backdrop-filter:blur(4px);
                                 font-family:'Syne',sans-serif;font-weight:700;">
                        <?php echo htmlspecialchars($row['CategoryName']); ?>
                    </span>
                </div>

                <div class="card-body d-flex flex-column" style="padding:1.25rem">
                    <h5 style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:.5rem">
                        <?php echo htmlspecialchars($row['Title']); ?>
                    </h5>
                    <p style="font-size:.85rem;color:#6b7280;line-height:1.5;flex-grow:1;margin-bottom:1rem">
                        <?php echo htmlspecialchars(mb_strimwidth($row['Description'], 0, 110, '...')); ?>
                    </p>

                    <!-- Progress -->
                    <div class="mb-1 d-flex justify-content-between" style="font-size:.78rem;color:#6b7280">
                        <span><strong style="color:#1a1a2e">৳<?php echo number_format($row['TotalAmount'], 0); ?></strong> raised</span>
                        <span><?php echo $row['DonorCount']; ?> donors</span>
                    </div>
                    <div class="progress mb-3">
                        <div class="progress-bar" style="width:<?php echo $percent; ?>%"></div>
                    </div>

                    <a href="donate.php?id=<?php echo (int)$row['PostID']; ?>"
                       class="btn btn-brand w-100" style="font-size:.875rem">
                        <i class="bi bi-heart me-1"></i> Donate Now
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>