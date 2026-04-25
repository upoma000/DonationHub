<?php
session_start();
include("db.php");

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];

if ($user['UserRole'] !== 'admin') {
    die("Access Denied");
}

include("navbar.php");

// ── Actions ──────────────────────────────────
if (isset($_GET['approve'])) {
    $id = (int) $_GET['approve'];
    $stmt = $conn->prepare("UPDATE DonationPost_T SET Status='approved' WHERE PostID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin.php?msg=approved");
    exit();
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM DonationPost_T WHERE PostID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin.php?msg=deleted");
    exit();
}

// ── Stats ──────────────────────────────────
$statUsers     = $conn->query("SELECT COUNT(*) AS n FROM User_T WHERE UserRole='user'")->fetch_assoc()['n'];
$statPosts     = $conn->query("SELECT COUNT(*) AS n FROM DonationPost_T")->fetch_assoc()['n'];
$statPending   = $conn->query("SELECT COUNT(*) AS n FROM DonationPost_T WHERE Status='pending'")->fetch_assoc()['n'];
$statDonations = $conn->query("SELECT IFNULL(SUM(Amount),0) AS s FROM Donation_T")->fetch_assoc()['s'];

// ── Filter ──────────────────────────────────
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$where = "";
if ($filterStatus === 'pending')  $where = "WHERE p.Status = 'pending'";
if ($filterStatus === 'approved') $where = "WHERE p.Status = 'approved'";

// ── Posts ──────────────────────────────────
$sql = "
    SELECT p.*, c.CategoryName, u.UserName,
           IFNULL(SUM(d.Amount),0) AS TotalAmount,
           COUNT(d.DonationID)     AS DonorCount
    FROM DonationPost_T p
    JOIN Category_T c  ON p.CategoryID = c.CategoryID
    JOIN User_T u      ON p.UserID     = u.UserID
    LEFT JOIN Donation_T d ON p.PostID = d.PostID
    $where
    GROUP BY p.PostID
    ORDER BY
        CASE WHEN p.Status='pending' THEN 0 ELSE 1 END,
        p.PostID DESC
";
$result = $conn->query($sql);
?>

<!-- Stats row -->
<div style="background:#fff;border-bottom:1px solid #e5e0da;padding:1.25rem 0">
    <div class="container">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="stat-val"><?php echo $statUsers; ?></div>
                        <div class="stat-lbl">Registered Users</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="bi bi-file-text"></i></div>
                    <div>
                        <div class="stat-val"><?php echo $statPosts; ?></div>
                        <div class="stat-lbl">Total Posts</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="stat-val"><?php echo $statPending; ?></div>
                        <div class="stat-lbl">Pending Review</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
                    <div>
                        <div class="stat-val">৳<?php echo number_format($statDonations, 0); ?></div>
                        <div class="stat-lbl">Total Donations</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4 pb-5">

    <!-- Flash message -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert" style="border-radius:10px;border:none;margin-bottom:1.25rem;font-size:.875rem;
             <?php echo $_GET['msg']==='approved' ? 'background:#dcfce7;color:#166534' : 'background:#fee2e2;color:#991b1b'; ?>">
            <i class="bi bi-<?php echo $_GET['msg']==='approved' ? 'check' : 'trash'; ?>-circle me-2"></i>
            <?php echo $_GET['msg'] === 'approved' ? 'Post approved and published.' : 'Post deleted.'; ?>
        </div>
    <?php endif; ?>

    <!-- Header + filter tabs -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h2 style="font-family:'Syne',sans-serif;font-size:1.5rem;margin:0">
                <i class="bi bi-speedometer2 me-2" style="color:#e8533a"></i>Admin Dashboard
            </h2>
            <p style="color:#6b7280;font-size:.875rem;margin:.25rem 0 0">Review and manage donation campaigns</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <?php foreach (['all'=>'All Posts','pending'=>'Pending','approved'=>'Approved'] as $k=>$label): ?>
                <a href="?status=<?php echo $k; ?>"
                   style="font-size:.8rem;font-family:'Syne',sans-serif;font-weight:700;padding:.4rem .9rem;
                          border-radius:99px;text-decoration:none;transition:all .15s;
                          <?php echo $filterStatus===$k
                              ? 'background:#1a1a2e;color:#fff'
                              : 'background:#f8f5f2;color:#6b7280;border:1px solid #e5e0da'; ?>">
                    <?php echo $label; ?>
                    <?php if ($k==='pending' && $statPending > 0): ?>
                        <span style="background:#e8533a;color:#fff;font-size:.65rem;padding:.1rem .4rem;border-radius:99px;margin-left:.3rem">
                            <?php echo $statPending; ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Table -->
    <?php if ($result->num_rows === 0): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No posts found</h5>
            <p>Nothing to show for the selected filter.</p>
        </div>
    <?php else: ?>
    <div style="background:#fff;border:1px solid #e5e0da;border-radius:16px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.05)">
        <div style="overflow-x:auto">
        <table class="fancy-table">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Submitted by</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Raised</th>
                    <th>Donors</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <!-- Campaign -->
                <td>
                    <div style="display:flex;align-items:center;gap:.75rem">
                        <?php if ($row['Image']): ?>
                            <img src="upload/<?php echo htmlspecialchars($row['Image']); ?>"
                                 style="width:44px;height:44px;object-fit:cover;border-radius:8px;flex-shrink:0">
                        <?php else: ?>
                            <div style="width:44px;height:44px;background:#f8f5f2;border-radius:8px;
                                        display:flex;align-items:center;justify-content:center;color:#9ca3af;flex-shrink:0">
                                <i class="bi bi-image"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:600;font-size:.875rem;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                <?php echo htmlspecialchars($row['Title']); ?>
                            </div>
                            <div style="font-size:.75rem;color:#9ca3af;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                <?php echo htmlspecialchars(mb_strimwidth($row['Description'], 0, 50, '...')); ?>
                            </div>
                        </div>
                    </div>
                </td>
                <!-- User -->
                <td style="font-size:.875rem"><?php echo htmlspecialchars($row['UserName']); ?></td>
                <!-- Category -->
                <td>
                    <span style="font-size:.75rem;background:#f8f5f2;color:#6b7280;
                                 padding:.2rem .6rem;border-radius:99px;font-weight:600;font-family:'Syne',sans-serif">
                        <?php echo htmlspecialchars($row['CategoryName']); ?>
                    </span>
                </td>
                <!-- Status -->
                <td>
                    <span class="badge-<?php echo $row['Status']; ?>"
                          style="font-size:.72rem;padding:.3rem .6rem;border-radius:6px;font-weight:700;
                                 font-family:'Syne',sans-serif;text-transform:uppercase">
                        <?php echo $row['Status']; ?>
                    </span>
                </td>
                <!-- Raised -->
                <td style="font-weight:600;color:#16a34a;font-size:.875rem">
                    ৳<?php echo number_format($row['TotalAmount'], 0); ?>
                </td>
                <!-- Donors -->
                <td style="font-size:.875rem;color:#6b7280"><?php echo $row['DonorCount']; ?></td>
                <!-- Date -->
                <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap">
                    <?php echo date('d M Y', strtotime($row['CreatedAt'])); ?>
                </td>
                <!-- Actions -->
                <td>
                    <div style="display:flex;gap:.4rem;flex-wrap:nowrap">
                        <?php if ($row['Status'] === 'pending'): ?>
                            <a href="?approve=<?php echo (int)$row['PostID']; ?>&status=<?php echo $filterStatus; ?>"
                               class="btn btn-sm"
                               style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0;
                                      border-radius:6px;font-size:.75rem;font-weight:600;padding:.3rem .7rem;white-space:nowrap"
                               onclick="return confirm('Approve this campaign?')">
                                <i class="bi bi-check-lg me-1"></i>Approve
                            </a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo (int)$row['PostID']; ?>&status=<?php echo $filterStatus; ?>"
                           class="btn btn-sm"
                           style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;
                                  border-radius:6px;font-size:.75rem;font-weight:600;padding:.3rem .7rem"
                           onclick="return confirm('Delete this post? This cannot be undone.')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>