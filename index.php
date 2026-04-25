<?php
session_start();
include("db.php");

// Already logged in → redirect
if (isset($_SESSION['user'])) {
    header("Location: " . ($_SESSION['user']['UserRole'] === 'admin' ? 'admin.php' : 'user.php'));
    exit();
}

$error = "";

if (isset($_POST['login'])) {
    $name = trim($_POST['name']);
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM User_T WHERE UserName = ? AND UserPassword = ?");
    $stmt->bind_param("ss", $name, $pass);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $_SESSION['user'] = $row;
        header("Location: " . ($row['UserRole'] === 'admin' ? 'admin.php' : 'user.php'));
        exit();
    } else {
        $error = "Invalid username or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DonateHub — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand:    #e8533a;
            --brand-dk: #c23f28;
            --brand-lt: #fdf1ef;
            --ink:      #1a1a2e;
            --muted:    #6b7280;
            --border:   #e5e0da;
            --bg:       #f8f5f2;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: grid;
            place-items: center;
        }
        h1,h2,h3 { font-family: 'Syne', sans-serif; }
        .auth-wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 900px;
            width: 100%;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,.12);
            margin: 2rem;
        }
        /* Left panel */
        .auth-panel {
            background: linear-gradient(160deg, #1a1a2e 0%, #2d2d4e 60%, #e8533a 140%);
            padding: 3rem 2.5rem;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .auth-panel .logo {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.6rem;
            color: #fff;
            letter-spacing: -1px;
        }
        .auth-panel .logo span { color: #e8533a; }
        .auth-panel .tagline {
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1.3;
            margin: 2rem 0 1rem;
        }
        .auth-panel .tagline em { color: #e8533a; font-style: normal; }
        .auth-panel p { opacity: .7; font-size: .9rem; line-height: 1.6; }
        .feature-list { list-style: none; padding: 0; margin: 2rem 0 0; }
        .feature-list li {
            display: flex; align-items: center; gap: .75rem;
            font-size: .875rem; opacity: .85; margin-bottom: .75rem;
        }
        .feature-list li i {
            width: 28px; height: 28px;
            background: rgba(255,255,255,.1);
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: .9rem;
        }
        /* Right panel */
        .auth-form { padding: 3rem 2.5rem; }
        .auth-form h2 { font-size: 1.5rem; margin-bottom: .25rem; color: #1a1a2e; }
        .auth-form .sub { color: var(--muted); font-size: .9rem; margin-bottom: 2rem; }
        .form-control {
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: .65rem .9rem;
            font-size: .9rem;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(232,83,58,.12);
        }
        label.form-label { font-weight: 500; font-size: .875rem; }
        .btn-brand {
            background: var(--brand);
            color: #fff;
            border: none;
            width: 100%;
            padding: .75rem;
            border-radius: 8px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            transition: background .15s, transform .1s;
        }
        .btn-brand:hover { background: var(--brand-dk); color:#fff; transform: translateY(-1px); }
        .alert { border-radius: 8px; border: none; font-size: .875rem; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .divider {
            text-align: center; position: relative; margin: 1.5rem 0;
            font-size: .8rem; color: var(--muted);
        }
        .divider::before {
            content: '';
            position: absolute; top: 50%; left: 0; right: 0;
            height: 1px; background: var(--border);
        }
        .divider span { background: #fff; position: relative; padding: 0 .75rem; }
        .demo-creds {
            background: var(--brand-lt);
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .8rem;
            color: var(--brand);
            border: 1px solid #f5c8c0;
        }
        .demo-creds strong { display: block; margin-bottom: .25rem; }
        @media (max-width: 640px) {
            .auth-wrap { grid-template-columns: 1fr; }
            .auth-panel { display: none; }
        }
    </style>
</head>
<body>

<div class="auth-wrap">
    <!-- Left Panel -->
    <div class="auth-panel">
        <div class="logo">Donate<span>Hub</span></div>
        <div>
            <div class="tagline">Make a <em>difference</em> in someone's life today</div>
            <p>Connect communities with those in need. Every donation matters, no matter the size.</p>
        </div>
        <ul class="feature-list">
            <li><i class="bi bi-heart-fill"></i> Support real causes near you</li>
            <li><i class="bi bi-shield-check"></i> Admin-verified donation posts</li>
            <li><i class="bi bi-graph-up"></i> Track every contribution</li>
            <li><i class="bi bi-phone"></i> bKash, Nagad & Visa support</li>
        </ul>
    </div>

    <!-- Right Panel -->
    <div class="auth-form">
        <h2>Welcome back</h2>
        <p class="sub">Sign in to continue to DonateHub</p>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-3">
                <i class="bi bi-exclamation-circle me-1"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0" style="border:1.5px solid #e5e0da;border-right:none;border-radius:8px 0 0 8px">
                        <i class="bi bi-person text-muted"></i>
                    </span>
                    <input type="text" name="name" class="form-control border-start-0"
                           style="border-left:none;border-radius:0 8px 8px 0"
                           placeholder="Enter username" required
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0" style="border:1.5px solid #e5e0da;border-right:none;border-radius:8px 0 0 8px">
                        <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input type="password" name="password" class="form-control border-start-0"
                           style="border-left:none;border-radius:0 8px 8px 0"
                           placeholder="Enter password" required>
                </div>
            </div>
            <button name="login" class="btn-brand">Sign In <i class="bi bi-arrow-right"></i></button>
        </form>

        <div class="divider"><span>or</span></div>

        <div class="demo-creds mb-3">
            <strong><i class="bi bi-info-circle me-1"></i> Demo Credentials</strong>
            Admin: <code>admin</code> / <code>admin123</code> &nbsp;|&nbsp;
            User: <code>demo_user</code> / <code>user123</code>
        </div>

        <p class="text-center mb-0" style="font-size:.875rem; color:var(--muted)">
            Don't have an account?
            <a href="signup.php" style="color:var(--brand);font-weight:600;text-decoration:none">Sign up free</a>
        </p>
    </div>
</div>

</body>
</html>