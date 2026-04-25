<?php
session_start();
include("db.php");

if (isset($_SESSION['user'])) {
    header("Location: " . ($_SESSION['user']['UserRole'] === 'admin' ? 'admin.php' : 'user.php'));
    exit();
}

$error   = "";
$success = "";

if (isset($_POST['signup'])) {
    $name = trim($_POST['name']);
    $pass = $_POST['password'];
    $conf = $_POST['confirm'];

    if (strlen($name) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (strlen($pass) < 4) {
        $error = "Password must be at least 4 characters.";
    } elseif ($pass !== $conf) {
        $error = "Passwords do not match.";
    } else {
        $role = "user";
        $stmt = $conn->prepare("INSERT INTO User_T (UserName, UserPassword, UserRole) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $pass, $role);

        try {
            $stmt->execute();
            $success = "Account created! You can now log in.";
        } catch (Exception $e) {
            $error = "Username already taken. Please choose another.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DonateHub — Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand:#e8533a; --brand-dk:#c23f28; --brand-lt:#fdf1ef;
            --ink:#1a1a2e; --muted:#6b7280; --border:#e5e0da; --bg:#f8f5f2;
        }
        * { box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); min-height:100vh; display:grid; place-items:center; }
        h1,h2,h3 { font-family:'Syne',sans-serif; }
        .auth-wrap {
            max-width:480px; width:100%; background:#fff;
            border-radius:20px; overflow:hidden;
            box-shadow:0 20px 60px rgba(0,0,0,.12); margin:2rem;
        }
        .auth-top {
            background:linear-gradient(135deg,#1a1a2e,#2d2d4e);
            padding:2rem 2.5rem 1.75rem;
            color:#fff;
        }
        .auth-top .logo { font-family:'Syne',sans-serif; font-weight:800; font-size:1.3rem; color:#fff; }
        .auth-top .logo span { color:#e8533a; }
        .auth-top h2 { font-size:1.4rem; margin:.75rem 0 .25rem; }
        .auth-top p { opacity:.65; font-size:.875rem; margin:0; }
        .auth-form { padding:2rem 2.5rem; }
        .form-control {
            border:1.5px solid var(--border); border-radius:8px;
            padding:.65rem .9rem; font-size:.9rem;
            transition:border-color .15s, box-shadow .15s;
        }
        .form-control:focus { border-color:var(--brand); box-shadow:0 0 0 3px rgba(232,83,58,.12); }
        label.form-label { font-weight:500; font-size:.875rem; }
        .btn-brand {
            background:var(--brand); color:#fff; border:none; width:100%;
            padding:.75rem; border-radius:8px;
            font-family:'Syne',sans-serif; font-weight:700; font-size:1rem;
            transition:background .15s, transform .1s;
        }
        .btn-brand:hover { background:var(--brand-dk); color:#fff; transform:translateY(-1px); }
        .alert { border-radius:8px; border:none; font-size:.875rem; }
        .alert-danger  { background:#fee2e2; color:#991b1b; }
        .alert-success { background:#dcfce7; color:#166534; }
    </style>
</head>
<body>

<div class="auth-wrap">
    <div class="auth-top">
        <div class="logo">Donate<span>Hub</span></div>
        <h2>Create your account</h2>
        <p>Join thousands making a difference every day</p>
    </div>

    <div class="auth-form">
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3">
                <i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success mb-3">
                <i class="bi bi-check-circle me-1"></i><?php echo htmlspecialchars($success); ?>
                <a href="index.php" style="color:#166534;font-weight:600">Log in now →</a>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="name" class="form-control"
                       placeholder="Choose a username" required
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Choose a password" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm" class="form-control"
                       placeholder="Repeat your password" required>
            </div>
            <button name="signup" class="btn-brand">Create Account <i class="bi bi-arrow-right"></i></button>
        </form>

        <p class="text-center mt-3 mb-0" style="font-size:.875rem;color:var(--muted)">
            Already have an account?
            <a href="index.php" style="color:var(--brand);font-weight:600;text-decoration:none">Sign in</a>
        </p>
    </div>
</div>

</body>
</html>