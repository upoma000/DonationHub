<?php
// navbar.php — included on all authenticated pages
// Requires session_start() and $user = $_SESSION['user'] to already be set
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DonateHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand:     #e8533a;
            --brand-dk:  #c23f28;
            --brand-lt:  #fdf1ef;
            --ink:       #1a1a2e;
            --muted:     #6b7280;
            --surface:   #ffffff;
            --bg:        #f8f5f2;
            --border:    #e5e0da;
            --success:   #22c55e;
            --warning:   #f59e0b;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
        }
        h1,h2,h3,h4,h5,h6,.brand {
            font-family: 'Syne', sans-serif;
        }
        /* ── Navbar ── */
        .top-nav {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
        }
        .nav-logo {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.25rem;
            color: var(--brand);
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        .nav-logo span { color: var(--ink); }
        .nav-links { display: flex; align-items: center; gap: .25rem; }
        .nav-links a {
            font-size: .875rem;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            padding: .4rem .75rem;
            border-radius: 6px;
            transition: all .15s;
        }
        .nav-links a:hover, .nav-links a.active {
            color: var(--brand);
            background: var(--brand-lt);
        }
        .nav-links a.logout {
            color: #ef4444;
        }
        .nav-links a.logout:hover { background: #fef2f2; }
        .nav-badge {
            font-size: .7rem;
            background: var(--brand);
            color: #fff;
            padding: .15rem .4rem;
            border-radius: 99px;
            margin-left: .25rem;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
        }
        /* ── Cards ── */
        .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
            transition: transform .2s, box-shadow .2s;
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }
        .card-img-top { border-radius: 14px 14px 0 0; }
        /* ── Buttons ── */
        .btn-brand {
            background: var(--brand);
            color: #fff;
            border: none;
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            border-radius: 8px;
            padding: .5rem 1.25rem;
            transition: background .15s, transform .1s;
        }
        .btn-brand:hover { background: var(--brand-dk); color: #fff; transform: translateY(-1px); }
        .btn-outline-brand {
            border: 1.5px solid var(--brand);
            color: var(--brand);
            background: transparent;
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            border-radius: 8px;
            padding: .5rem 1.25rem;
            transition: all .15s;
        }
        .btn-outline-brand:hover { background: var(--brand); color: #fff; }
        /* ── Progress bar ── */
        .progress { height: 6px; border-radius: 99px; background: var(--border); }
        .progress-bar { background: var(--brand); border-radius: 99px; }
        /* ── Badge ── */
        .badge-pending  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-approved { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        /* ── Page header ── */
        .page-header {
            background: linear-gradient(135deg, var(--ink) 0%, #2d2d4e 100%);
            color: #fff;
            padding: 2.5rem 0 2rem;
            margin-bottom: 2rem;
        }
        .page-header h2 { font-size: 1.8rem; margin: 0; }
        .page-header p  { margin: .25rem 0 0; opacity: .7; font-size: .9rem; }
        /* ── Stats card ── */
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .stat-icon.red   { background: var(--brand-lt); color: var(--brand); }
        .stat-icon.green { background: #dcfce7; color: #16a34a; }
        .stat-icon.blue  { background: #dbeafe; color: #1d4ed8; }
        .stat-icon.amber { background: #fef3c7; color: #d97706; }
        .stat-val { font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .stat-lbl { font-size: .8rem; color: var(--muted); margin-top: .2rem; }
        /* ── Table ── */
        .fancy-table { border-collapse: separate; border-spacing: 0; width: 100%; }
        .fancy-table thead th {
            background: var(--bg);
            font-family: 'Syne', sans-serif;
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            padding: .85rem 1rem;
        }
        .fancy-table tbody td {
            padding: .85rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            font-size: .9rem;
            background: var(--surface);
        }
        .fancy-table tbody tr:last-child td { border-bottom: none; }
        .fancy-table tbody tr:hover td { background: var(--brand-lt); }
        /* ── Form ── */
        .form-control, .form-select {
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: .9rem;
            padding: .6rem .9rem;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(232,83,58,.12);
        }
        label.form-label { font-weight: 500; font-size: .875rem; margin-bottom: .35rem; }
        /* ── Alert ── */
        .alert { border-radius: 10px; border: none; font-size: .9rem; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-danger  { background: #fee2e2; color: #991b1b; }
        /* ── Misc ── */
        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            padding-bottom: .5rem;
            border-bottom: 2px solid var(--border);
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--muted);
        }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; opacity: .4; }
    </style>
</head>
<body>

<nav class="top-nav">
    <a href="<?php echo ($user['UserRole']==='admin') ? 'admin.php' : 'user.php'; ?>" class="nav-logo">
        Donate<span>Hub</span>
    </a>
    <div class="nav-links">
        <?php if($user['UserRole'] === 'admin'): ?>
            <a href="admin.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <?php else: ?>
            <a href="user.php"><i class="bi bi-grid-1x2"></i> Browse</a>
            <a href="create.php"><i class="bi bi-plus-circle"></i> Create Post</a>
        <?php endif; ?>
        <span style="color:var(--border);margin:0 .25rem">|</span>
        <span style="font-size:.85rem;color:var(--muted);padding:.4rem .5rem">
            <i class="bi bi-person-circle"></i>
            <?php echo htmlspecialchars($user['UserName']); ?>
            <span class="nav-badge"><?php echo $user['UserRole']; ?></span>
        </span>
        <a href="logout.php" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</nav>