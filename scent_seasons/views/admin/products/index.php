<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin(); // 必须是管理员

// 处理搜索
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$sql = "SELECT p.*, c.category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.category_id 
        WHERE p.name LIKE ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%"]);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Product Management</title>
    <link rel="stylesheet" href="../../../css/style.css">
    <style>
        /* 一些简单的表格样式 */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .btn-red {
            color: white;
            background: red;
            padding: 5px 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .btn-blue {
            color: white;
            background: blue;
            padding: 5px 10px;
            text-decoration: none;
        }

        .thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <nav>
        <a href="#" class="logo">Admin Panel</a>
        <ul>
            <li><a href="index.php">Products</a></li>
            <li><a href="../../../logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Product List</h2>

        <div style="display:flex; justify-content:space-between;">
            <a href="create.php" class="btn-blue">+ Add New Product</a>

            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search product..." value="<?php echo $search; ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['image_path']): ?>
                                <img src="../../../images/products/<?php echo $p['image_path']; ?>" class="thumbnail">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?php echo $p['name']; ?></td>
                        <td><?php echo $p['category_name']; ?></td>
                        <td>$<?php echo $p['price']; ?></td>
                        <td><?php echo $p['stock']; ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $p['product_id']; ?>" class="btn-blue">Edit</a>

                            <form action="../../../controllers/product_controller.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                <button type="submit" class="btn-red">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>