<?php
require 'config/database.php';
?>
<!DOCTYPE html>
<html>

<head>
    <title>Scent Seasons</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <nav>
        <a href="#" class="logo">Scent Seasons</a>
        <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#">Login</a></li>
        </ul>
    </nav>
    <div class="container">
        <h1>Welcome to Scent Seasons</h1>
        <p>Database Connection Status:
            <?php echo isset($pdo) ? "<strong style='color:green'>Success!</strong>" : "Failed"; ?>
        </p>
    </div>
</body>

</html>