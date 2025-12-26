<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();


$stmt_cats = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = $stmt_cats->fetchAll();


$stmt_vids = $pdo->query("SELECT * FROM youtube_videos ORDER BY id DESC");
$videos = $stmt_vids->fetchAll();


$stmt_imgs = $pdo->query("SELECT * FROM product_images");
$all_images = $stmt_imgs->fetchAll();
$gallery_map = [];
foreach ($all_images as $img) {
    $gallery_map[$img['product_id']][] = [
        'id' => $img['id'],
        'path' => $img['image_path']
    ];
}


$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';


$sql = "SELECT p.*, c.category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.category_id 
        WHERE p.is_deleted = 0 AND p.name LIKE ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%"]);
$products = $stmt->fetchAll();


$sql_trash = "SELECT p.*, c.category_name 
              FROM products p 
              JOIN categories c ON p.category_id = c.category_id 
              WHERE p.is_deleted = 1 ORDER BY p.product_id DESC";
$stmt_trash = $pdo->query($sql_trash);
$deleted_products = $stmt_trash->fetchAll();

$page_title = "Product Management";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div class="flex-between mb-20">
    <h2>Product List</h2>
    <button id="btn-open-recycle" class="btn-orange">
        View Recycle Bin (<?php echo count($deleted_products); ?>)
    </button>
</div>

<div class="flex-between mb-20">
    <button id="btn-open-create" class="btn-blue">+ Add New Product</button>

    <form method="GET" action="" class="search-form">
        <input type="text" name="search" placeholder="Search product..." value="<?php echo $search; ?>">
        <button type="submit" class="btn-blue">Search</button>
    </form>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert <?php echo ($_GET['msg'] == 'deleted_permanent') ? 'alert-error' : 'alert-success'; ?>">
        <?php
        if ($_GET['msg'] == 'added') echo "Product added successfully.";
        elseif ($_GET['msg'] == 'trashed') echo "Product moved to trash.";
        elseif ($_GET['msg'] == 'restored') echo "Product restored from trash.";
        elseif ($_GET['msg'] == 'deleted_permanent') echo "Product permanently deleted.";
        elseif ($_GET['msg'] == 'updated') echo "Product updated successfully.";
        ?>
    </div>
<?php endif; ?>

<table class="table-list">
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
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $p): 
                
                $p_gallery = isset($gallery_map[$p['product_id']]) ? json_encode($gallery_map[$p['product_id']]) : '[]';
            ?>
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
                        <button type="button"
                            class="btn-blue js-open-edit"
                            data-id="<?php echo $p['product_id']; ?>"
                            data-name="<?php echo htmlspecialchars($p['name']); ?>"
                            data-cat="<?php echo $p['category_id']; ?>"
                            data-price="<?php echo $p['price']; ?>"
                            data-stock="<?php echo $p['stock']; ?>"
                            data-desc="<?php echo htmlspecialchars($p['description']); ?>"
                            data-img="<?php echo $p['image_path']; ?>"
                            data-video="<?php echo isset($p['youtube_video_id']) ? $p['youtube_video_id'] : ''; ?>"
                            data-gallery='<?php echo htmlspecialchars($p_gallery, ENT_QUOTES, 'UTF-8'); ?>' >
                            Edit
                        </button>

                        <button type="button"
                            class="btn-red js-open-trash"
                            data-id="<?php echo $p['product_id']; ?>">
                            Trash
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center text-gray" style="padding:20px;">No active products found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div id="createProductModal" class="modal-overlay">
    <div class="modal-box medium">
        <h3 class="mt-0">Add New Product</h3>
        <form action="../../../controllers/product_controller.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label>Product Name:</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Category:</label>
                <select name="category_id" required>
                    <option value="">Select Category...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-gap-10">
                <div class="form-group w-100">
                    <label>Price ($):</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                <div class="form-group w-100">
                    <label>Stock:</label>
                    <input type="number" name="stock" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label>Main Image (Cover):</label>
                <input type="file" name="image" accept="image/*" required class="input-file-custom">
            </div>

            <div class="form-group">
                <label>Gallery Images (Multiple):</label>
                <input type="file" name="gallery_images[]" accept="image/*" multiple class="input-file-custom">
                <small class="text-muted">Hold Ctrl/Cmd to select multiple images.</small>
            </div>

            <div class="form-group" style="border-top: 1px dashed #ccc; padding-top: 15px; margin-top: 15px;">
                <label>Product Video (YouTube):</label>
                <select name="existing_video_id" class="form-control" style="width: 100%; padding: 8px; margin-bottom: 5px;">
                    <option value="">-- No Video / Select Existing --</option>
                    <?php foreach ($videos as $v): ?>
                        <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['url']); ?></option>
                    <?php endforeach; ?>
                </select>
                <p style="text-align:center; margin: 5px 0; font-size: 12px; color: #888;">- OR -</p>
                <input type="text" name="new_video_url" placeholder="Paste new YouTube URL here..." class="form-control" style="width: 100%; padding: 8px;">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-disabled js-close-modal">Cancel</button>
                <button type="submit" class="btn-green">Save Product</button>
            </div>
        </form>
    </div>
</div>

<div id="editProductModal" class="modal-overlay">
    <div class="modal-box medium">
        <h3 class="mt-0">Edit Product</h3>
        <form action="../../../controllers/product_controller.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="product_id" id="edit_product_id">

            <div class="form-group"><label>Product Name:</label><input type="text" name="name" id="edit_name" required></div>
            <div class="form-group">
                <label>Category:</label>
                <select name="category_id" id="edit_category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-gap-10">
                <div class="form-group w-100"><label>Price ($):</label><input type="number" step="0.01" name="price" id="edit_price" required></div>
                <div class="form-group w-100"><label>Stock:</label><input type="number" name="stock" id="edit_stock" required></div>
            </div>
            <div class="form-group"><label>Description:</label><textarea name="description" id="edit_description" rows="3"></textarea></div>

            <div class="form-group">
                <label>Current Main Image: <span id="edit_current_img_name" class="text-preview-info"></span></label>
                <small class="text-muted">Upload new image to replace:</small>
                <input type="file" name="image" accept="image/*" class="input-file-custom">
            </div>

            <div class="form-group" style="background: #f9f9f9; padding: 10px; border-radius: 5px;">
                <label>Gallery Images:</label>
                
                <div id="edit_gallery_preview" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                    </div>

                <label style="font-size: 0.9em; margin-top:5px;">Add More Images:</label>
                <input type="file" name="gallery_images[]" accept="image/*" multiple class="input-file-custom">
            </div>

            <div class="form-group" style="border-top: 1px dashed #ccc; padding-top: 15px; margin-top: 15px;">
                <label>Product Video (YouTube):</label>
                <select name="existing_video_id" id="edit_video_id" class="form-control" style="width: 100%; padding: 8px; margin-bottom: 5px;">
                    <option value="">-- No Video / Select Existing --</option>
                    <?php foreach ($videos as $v): ?>
                        <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['url']); ?></option>
                    <?php endforeach; ?>
                </select>
                <p style="text-align:center; margin: 5px 0; font-size: 12px; color: #888;">- OR -</p>
                <input type="text" name="new_video_url" placeholder="Change to new YouTube URL..." class="form-control" style="width: 100%; padding: 8px;">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-disabled js-close-modal">Cancel</button>
                <button type="submit" class="btn-blue">Update Product</button>
            </div>
        </form>
    </div>
</div>

<div id="trashConfirmModal" class="modal-overlay">
    <div class="modal-box small text-center">
        <h3 class="mt-0 text-red-bold" style="color:#c0392b;">Move to Trash?</h3>
        <p class="text-gray mb-20">Are you sure you want to move this product to the trash? <br>It will be hidden from the shop but can be restored later.</p>
        <form action="../../../controllers/product_controller.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" id="trash_confirm_id" value="">
            <div class="modal-actions center">
                <button type="button" class="btn-disabled js-close-modal">Cancel</button>
                <button type="submit" class="btn-red">Confirm Trash</button>
            </div>
        </form>
    </div>
</div>

<div id="recycleBinModal" class="modal-overlay">
    <div class="modal-box large">
        <div class="modal-header"><h3>Recycle Bin (<?php echo count($deleted_products); ?> items)</h3><button class="modal-close-btn js-close-modal">&times;</button></div>
        <?php if (count($deleted_products) == 0): ?><p class="text-center text-gray" style="padding:20px;">The trash is empty.</p><?php else: ?>
            <table class="table-list">
                <thead><tr><th>Image</th><th>Name</th><th>Price</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($deleted_products as $dp): ?>
                        <tr>
                            <td><?php if ($dp['image_path']): ?><img src="../../../images/products/<?php echo $dp['image_path']; ?>" class="thumbnail" style="width:40px; height:40px;"><?php endif; ?></td>
                            <td><?php echo $dp['name']; ?></td>
                            <td>$<?php echo $dp['price']; ?></td>
                            <td>
                                <form action="../../../controllers/product_controller.php" method="POST" style="display:inline;"><input type="hidden" name="action" value="restore"><input type="hidden" name="product_id" value="<?php echo $dp['product_id']; ?>"><button type="submit" class="btn-green" style="font-size:0.8em; padding:5px 10px;">Restore</button></form>
                                <form action="../../../controllers/product_controller.php" method="POST" style="display:inline; margin-left:5px;" onsubmit="return confirm('WARNING: This will delete the product PERMANENTLY. Continue?');"><input type="hidden" name="action" value="delete_permanent"><input type="hidden" name="product_id" value="<?php echo $dp['product_id']; ?>"><button type="submit" class="btn-darkred">Delete</button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <div class="modal-actions"><button class="btn-disabled js-close-modal">Close</button></div>
    </div>
</div>

<script>
    $(document).ready(function() {
      
        $('#btn-open-create').on('click', function() { $('#createProductModal').css('display', 'flex'); });
        $('#btn-open-recycle').on('click', function() { $('#recycleBinModal').css('display', 'flex'); });

        
        $(document).on('click', '.js-open-edit', function() {
            let btn = $(this);
            $('#edit_product_id').val(btn.data('id'));
            $('#edit_name').val(btn.data('name'));
            $('#edit_category_id').val(btn.data('cat'));
            $('#edit_price').val(btn.data('price'));
            $('#edit_stock').val(btn.data('stock'));
            $('#edit_description').val(btn.data('desc'));
            $('#edit_video_id').val(btn.data('video'));

            let img = btn.data('img');
            $('#edit_current_img_name').text(img ? "(" + img + ")" : "(No Image)");

            let galleryData = btn.data('gallery'); 
            let galleryHtml = '';
            
            if (galleryData && galleryData.length > 0) {
                galleryData.forEach(function(img) {
                    galleryHtml += `
                        <div class="gallery-item" id="gallery-img-${img.id}" style="position: relative; width: 60px; height: 60px; border: 1px solid #ddd;">
                            <img src="../../../images/products/${img.path}" style="width:100%; height:100%; object-fit:cover;">
                            <button type="button" class="btn-remove-img" data-id="${img.id}" 
                                style="position:absolute; top:-5px; right:-5px; background:red; color:white; border:none; border-radius:50%; width:18px; height:18px; font-size:12px; cursor:pointer; line-height:1;">&times;</button>
                        </div>
                    `;
                });
            } else {
                galleryHtml = '<p class="text-muted" style="font-size:0.8em;">No gallery images.</p>';
            }
            $('#edit_gallery_preview').html(galleryHtml);

            $('#editProductModal').css('display', 'flex');
        });

    
        $(document).on('click', '.btn-remove-img', function() {
            let imgId = $(this).data('id');
            let parentDiv = $(this).parent();

            if(!confirm("Delete this image?")) return;

            $.post('../../../controllers/product_controller.php', {
                action: 'delete_gallery_image',
                image_id: imgId
            }, function(response) {
                if (response.trim() === 'success') {
                    parentDiv.remove();
                } else {
                    alert('Failed to delete image.');
                }
            });
        });

        $(document).on('click', '.js-open-trash', function() {
            $('#trash_confirm_id').val($(this).data('id'));
            $('#trashConfirmModal').css('display', 'flex');
        });

        $(document).on('click', '.js-close-modal', function() {
            $(this).closest('.modal-overlay').css('display', 'none');
        });

        $(window).on('click', function(event) {
            if ($(event.target).hasClass('modal-overlay')) {
                $(event.target).css('display', 'none');
            }
        });

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('open_trash')) {
            $('#recycleBinModal').css('display', 'flex');
        }
    });
</script>

<?php require $path . 'includes/footer.php'; ?>