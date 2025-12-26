<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';


$stmt = $pdo->query("SELECT * FROM promotion_codes ORDER BY created_at DESC");
$promotions = $stmt->fetchAll();


$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($action == 'create' || $action == 'update' || $action == 'delete') {
  
    if (!is_admin()) {
        die("Access Denied: You must be an administrator.");
    }
    
   
   
    
if ($action == 'create') {
    $code = strtoupper(clean_input($_POST['code']));
    $discount_type = clean_input($_POST['discount_type']);
    $discount_value = floatval($_POST['discount_value']);
    $min_purchase = floatval($_POST['min_purchase']);
    $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : NULL;
    $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : NULL;
    $start_date = !empty($_POST['start_date']) ? clean_input($_POST['start_date']) : NULL;
    $end_date = !empty($_POST['end_date']) ? clean_input($_POST['end_date']) : NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $sql = "INSERT INTO promotion_codes (code, discount_type, discount_value, min_purchase, max_discount, usage_limit, start_date, end_date, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

  
    try {
    
        $stmt->execute([$code, $discount_type, $discount_value, $min_purchase, $max_discount, $usage_limit, $start_date, $end_date, $is_active]);
        
        log_activity($pdo, "Create Promotion Code", "Code: $code");

     
        header("Location: ../views/admin/promotion.php?msg=created");
        exit();

    } catch (PDOException $e) {
       
        if ($e->errorInfo[1] == 1062) {
           
            if (is_admin()) {
                header("Location: ../views/admin/promotion.php?error=duplicate");
            } else {
                header("Location: ../views/member/promotions.php?error=duplicate");
            }
            exit();
        } else {
           
            die("Database Error: " . $e->getMessage());
        }
    }
  
}
    
    if ($action == 'update') {
        $code_id = intval($_POST['code_id']);
        $code = strtoupper(clean_input($_POST['code']));
        $discount_type = clean_input($_POST['discount_type']);
        $discount_value = floatval($_POST['discount_value']);
        $min_purchase = floatval($_POST['min_purchase']);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : NULL;
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : NULL;
        $start_date = !empty($_POST['start_date']) ? clean_input($_POST['start_date']) : NULL;
        $end_date = !empty($_POST['end_date']) ? clean_input($_POST['end_date']) : NULL;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE promotion_codes SET code = ?, discount_type = ?, discount_value = ?, min_purchase = ?, 
                max_discount = ?, usage_limit = ?, start_date = ?, end_date = ?, is_active = ? 
                WHERE code_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$code, $discount_type, $discount_value, $min_purchase, $max_discount, $usage_limit, $start_date, $end_date, $is_active, $code_id]);
        
        log_activity($pdo, "Update Promotion Code", "Code ID: $code_id");
     
     header("Location: ../views/admin/promotion.php?msg=updated");
exit();
    }
    
    if ($action == 'delete') {
        $code_id = intval($_POST['code_id']);
        $stmt = $pdo->prepare("DELETE FROM promotion_codes WHERE code_id = ?");
        $stmt->execute([$code_id]);
        
        log_activity($pdo, "Delete Promotion Code", "Code ID: $code_id");
   
        header("Location: ../views/admin/promotion.php?msg=deleted");
        exit();
    }
}


if ($action == 'validate') {
    if (!is_logged_in()) {
        echo json_encode(['status' => 'error', 'message' => 'Please login first']);
        exit();
    }
    
    $code = strtoupper(clean_input($_GET['code']));
    $total_amount = floatval($_GET['total']);
    
    $stmt = $pdo->prepare("SELECT * FROM promotion_codes WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $promo = $stmt->fetch();
    
    if (!$promo) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid promotion code']);
        exit();
    }
    
   
    $today = date('Y-m-d');
    if ($promo['start_date'] && $promo['start_date'] > $today) {
        echo json_encode(['status' => 'error', 'message' => 'Promotion code not yet active']);
        exit();
    }
    if ($promo['end_date'] && $promo['end_date'] < $today) {
        echo json_encode(['status' => 'error', 'message' => 'Promotion code has expired']);
        exit();
    }
    
    
    if ($total_amount < $promo['min_purchase']) {
        echo json_encode(['status' => 'error', 'message' => 'Minimum purchase amount not met']);
        exit();
    }
    
  
    if ($promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit']) {
        echo json_encode(['status' => 'error', 'message' => 'Promotion code usage limit reached']);
        exit();
    }
    
  
    $discount = 0;
    if ($promo['discount_type'] == 'percentage') {
        $discount = ($total_amount * $promo['discount_value']) / 100;
        if ($promo['max_discount'] && $discount > $promo['max_discount']) {
            $discount = $promo['max_discount'];
        }
    } else {
        $discount = $promo['discount_value'];
        if ($discount > $total_amount) {
            $discount = $total_amount;
        }
    }
    
    $final_amount = $total_amount - $discount;
    
    echo json_encode([
        'status' => 'success',
        'discount' => round($discount, 2),
        'final_amount' => round($final_amount, 2),
        'discount_type' => $promo['discount_type'],
        'discount_value' => $promo['discount_value']
    ]);
    exit();
}
?>

