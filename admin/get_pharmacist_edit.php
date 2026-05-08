<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Unauthorized');
}

include '../includes/config.php';

if (isset($_GET['id'])) {
    $pharmacist_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT ph.*, u.username, u.email 
                           FROM pharmacists ph 
                           JOIN users u ON ph.user_id = u.id 
                           WHERE ph.id = ?");
    $stmt->bind_param("i", $pharmacist_id);
    $stmt->execute();
    $pharmacist = $stmt->get_result()->fetch_assoc();
    
    if ($pharmacist) {
?>
<form method="POST" action="process_edit_pharmacist.php">
    <input type="hidden" name="pharmacist_id" value="<?php echo $pharmacist['id']; ?>">
    <input type="hidden" name="user_id" value="<?php echo $pharmacist['user_id']; ?>">
    
    <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="full_name" value="<?php echo htmlspecialchars($pharmacist['full_name']); ?>" required>
    </div>
    
    <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($pharmacist['email']); ?>" required>
    </div>
    
    <div class="form-group">
        <label>Pharmacy Name *</label>
        <input type="text" name="pharmacy_name" value="<?php echo htmlspecialchars($pharmacist['pharmacy_name']); ?>" required>
    </div>
    
    <div class="form-group">
        <label>Phone</label>
        <input type="tel" name="phone" value="<?php echo htmlspecialchars($pharmacist['phone']); ?>">
    </div>
    
    <div class="form-group">
        <label>Address</label>
        <textarea name="address" rows="3"><?php echo htmlspecialchars($pharmacist['address']); ?></textarea>
    </div>
    
    <button type="submit" class="btn btn-glass-primary" style="width: 100%; margin-top: 20px;">Save Changes</button>
</form>
<?php
    } else {
        echo '<p>Pharmacist not found.</p>';
    }
}
?>
