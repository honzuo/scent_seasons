<?php
session_start();
require '../../includes/functions.php';
require_admin(); // 强制检查是否是管理员
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .card h3 {
            margin-bottom: 10px;
        }

        .card a {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <nav>
        <a href="#" class="logo">Scent Seasons (Admin)</a>
        <ul>
            <li><a href="../../logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>Welcome, Admin!</h1>
        <p>Manage your perfume shop from here.</p>

        <div class="dashboard-grid">
            <div class="card">
                <h3>Products</h3>
                <p>Manage perfumes, stock & prices.</p>
                <a href="products/index.php">Go to Products &rarr;</a>
            </div>

            <div class="card">
                <h3>Members</h3>
                <p>View registered members.</p>
                <a href="#">Go to Members &rarr;</a>
            </div>

            <div class="card">
                <h3>Orders</h3>
                <p>View customer orders.</p>
                <a href="orders/index.php">Go to Orders &rarr;</a>
            </div>
        </div>
    </div>
</body>

</html>