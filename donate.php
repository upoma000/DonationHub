<?php
session_start();
include("db.php");

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];

// Validate post ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user.php");
    exit();
}

$id = (int) $_GET['id'];

// Fetch post (must be approved)
$stmt = $conn->prepare("
    SELECT p.*, c.CategoryName,
           IFNULL(SUM(d.Amount),0)   AS TotalAmount,
           COUNT(d.DonationID)       AS DonorCount
    FROM DonationPost_T p
    JOIN Category_T c ON p.CategoryID = c.CategoryID
    LEFT JOIN Donation_T d ON p.PostID = d.PostID
    WHERE p.PostID = ? AND p.Status = 'approved'
    GROUP BY p.PostID
");
$stmt->bind_param("i", $id);
$stmt->execute();
$postRes = $stmt->get_result();
$stmt->close();

if ($postRes->num_rows === 0) {
    header("Location: user.php");
    exit();
}

$post = $postRes->fetch_assoc();

include("navbar.php");

$message     = "";
$messageType = "success";

if (isset($_POST['pay'])) {
    $amount  = $_POST['amount'];
    $method  = $_POST['method'];
    $allowed = ['bKash', 'Nagad', 'Visa'];

    if (!is_numeric($amount) || (float)$amount <= 0) {
        $message     = "Please enter a valid donation amount.";
        $messageType = "danger";
    } elseif (!in_array($method, $allowed)) {
        $message     = "Invalid payment method.";
        $messageType = "danger";
    } else {
        $amt  = (float) $amount;
        $stmt = $conn->prepare(
            "INSERT INTO Donation_T (PostID, Amount, PaymentMethod) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("ids", $id, $amt, $method);

        if ($stmt->execute()) {
            $message = "🎉 Payment successful! Thank you for your donation of ৳" . number_format($amt, 2) . " via $method.";
            // Refresh total
            $post['TotalAmount'] += $amt;
            $post['DonorCount']++;
        } else {
            $message     = "Payment failed. Please try again.";
            $messageType = "danger";
        }
        $stmt->close();
    }
}

$methodIcons = ['bKash' => 'bi-phone', 'Nagad' => 'bi-phone-fill', 'Visa' => 'bi-credit-card'];
?>

<div class="container mt-4 pb-5" style="max-width:900px">
    <div class="mb-3">
        <a href="user.php" style="color:#6b7280;font-size:.875rem;text-decoration:none">
            <i class="bi bi-arrow-left me-1"></i> Back to campaigns
        </a>
    </div>

    <div class="row g-4">
        <!-- Left: post info -->
        <div class="col-md-6">
            <div class="card h-100" style="border-radius:16px">
                <?php if ($post['Image']): ?>
                    <img src="upload/<?php echo htmlspecialchars($post['Image']); ?>"
                         class="card-img-top"
                         style="height:220px;object-fit:cover;border-radius:16px 16px 0 0"
                         alt="<?php echo htmlspecialchars($post['Title']); ?>">
                <?php endif; ?>
                <div class="card-body" style="padding:1.5rem">
                    <span style="font-size:.75rem;font-weight:700;background:#fdf1ef;color:#e8533a;padding:.2rem .6rem;border-radius:99px;font-family:'Syne',sans-serif">
                        <?php echo htmlspecialchars($post['CategoryName']); ?>
                    </span>
                    <h4 style="font-family:'Syne',sans-serif;font-size:1.2rem;margin:1rem 0 .75rem">
                        <?php echo htmlspecialchars($post['Title']); ?>
                    </h4>
                    <p style="font-size:.875rem;color:#6b7280;line-height:1.6;margin-bottom:1.25rem">
                        <?php echo nl2br(htmlspecialchars($post['Description'])); ?>
                    </p>

                    <!-- Stats -->
                    <div style="background:#f8f5f2;border-radius:10px;padding:1rem;margin-top:auto">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span style="font-size:.8rem;color:#6b7280">Total Raised</span>
                            <strong style="font-family:'Syne',sans-serif;color:#16a34a;font-size:1.1rem">
                                ৳<?php echo number_format($post['TotalAmount'], 0); ?>
                            </strong>
                        </div>
                        <div class="progress mb-2">
                            <?php $pct = min(100, round($post['TotalAmount'] / max($post['TotalAmount'] * 1.5, 5000) * 100)); ?>
                            <div class="progress-bar" style="width:<?php echo $pct; ?>%"></div>
                        </div>
                        <div style="font-size:.78rem;color:#6b7280">
                            <i class="bi bi-people me-1"></i><?php echo $post['DonorCount']; ?> donors
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: payment form -->
        <div class="col-md-6">
            <div class="card" style="border-radius:16px">
                <div class="card-body" style="padding:1.75rem">
                    <h5 style="font-family:'Syne',sans-serif;font-size:1.1rem;margin-bottom:1.5rem">
                        <i class="bi bi-heart-fill me-2" style="color:#e8533a"></i>Make a Donation
                    </h5>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> mb-3" style="border-radius:10px;border:none;font-size:.875rem;
                             <?php echo $messageType==='success' ? 'background:#dcfce7;color:#166534' : 'background:#fee2e2;color:#991b1b'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <!-- Payment method selection -->
                        <div class="mb-4">
                            <label class="form-label">Payment Method</label>
                            <div class="d-flex gap-2">
                                <?php foreach (['bKash', 'Nagad', 'Visa'] as $m):
                                    $colors = [
                                        'bKash' => ['bg'=>'#e8f4fd','border'=>'#93c5fd','text'=>'#1e40af'],
                                        'Nagad' => ['bg'=>'#fff7ed','border'=>'#fcd34d','text'=>'#92400e'],
                                        'Visa'  => ['bg'=>'#f0fdf4','border'=>'#86efac','text'=>'#166534'],
                                    ];
                                    $c = $colors[$m];
                                ?>
                                <label style="flex:1;cursor:pointer">
                                    <input type="radio" name="method" value="<?php echo $m; ?>" style="display:none" class="method-radio"
                                           <?php echo ($m === 'bKash') ? 'checked' : ''; ?>>
                                    <div class="method-btn" data-method="<?php echo $m; ?>"
                                         style="border:2px solid <?php echo ($m==='bKash') ? $c['border'] : '#e5e0da'; ?>;
                                                background:<?php echo ($m==='bKash') ? $c['bg'] : '#fff'; ?>;
                                                border-radius:10px;padding:.7rem .5rem;text-align:center;
                                                color:<?php echo ($m==='bKash') ? $c['text'] : '#6b7280'; ?>;
                                                transition:all .15s;font-size:.85rem;font-weight:600;font-family:'Syne',sans-serif">
                                        <i class="bi <?php echo $methodIcons[$m]; ?> d-block mb-1" style="font-size:1.2rem"></i>
                                        <?php echo $m; ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Quick amounts -->
                        <div class="mb-3">
                            <label class="form-label">Donation Amount (BDT)</label>
                            <div class="d-flex gap-2 mb-2 flex-wrap">
                                <?php foreach ([100, 250, 500, 1000] as $preset): ?>
                                    <button type="button" class="btn btn-sm quick-amt"
                                            onclick="document.getElementById('amtInput').value=<?php echo $preset; ?>"
                                            style="border:1.5px solid #e5e0da;background:#fff;border-radius:8px;
                                                   font-family:'Syne',sans-serif;font-weight:600;font-size:.8rem;
                                                   padding:.35rem .75rem;transition:all .15s">
                                        ৳<?php echo number_format($preset); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="number" id="amtInput" name="amount" class="form-control"
                                   placeholder="Or enter custom amount" min="1" step="1" required>
                        </div>

                        <button name="pay" class="btn btn-brand w-100 mt-2">
                            <i class="bi bi-heart me-1"></i> Confirm Donation
                        </button>
                    </form>

                    <p style="font-size:.75rem;color:#9ca3af;text-align:center;margin-top:1rem;margin-bottom:0">
                        <i class="bi bi-shield-check me-1"></i>
                        This is a demo system. No real payments are processed.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Payment method radio styling
document.querySelectorAll('.method-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        const colors = {
            bKash: { bg:'#e8f4fd', border:'#93c5fd', text:'#1e40af' },
            Nagad: { bg:'#fff7ed', border:'#fcd34d', text:'#92400e' },
            Visa:  { bg:'#f0fdf4', border:'#86efac', text:'#166534' }
        };
        document.querySelectorAll('.method-btn').forEach(btn => {
            if (btn.dataset.method === radio.value) {
                const c = colors[radio.value];
                btn.style.border     = `2px solid ${c.border}`;
                btn.style.background = c.bg;
                btn.style.color      = c.text;
            } else {
                btn.style.border     = '2px solid #e5e0da';
                btn.style.background = '#fff';
                btn.style.color      = '#6b7280';
            }
        });
    });
});
// Quick amount buttons hover
document.querySelectorAll('.quick-amt').forEach(btn => {
    btn.addEventListener('mouseenter', () => { btn.style.borderColor='#e8533a'; btn.style.color='#e8533a'; });
    btn.addEventListener('mouseleave', () => { btn.style.borderColor='#e5e0da'; btn.style.color=''; });
});
</script>
</body>
</html>