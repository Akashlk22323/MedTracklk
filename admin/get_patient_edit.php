<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Unauthorized');
}

include '../includes/config.php';

if (isset($_GET['id'])) {
    $patient_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT p.*, u.username, u.email 
                           FROM patients p 
                           JOIN users u ON p.user_id = u.id 
                           WHERE p.id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    
    if ($patient) {
?>
<form method="POST" action="process_edit_patient.php">
    <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
    <input type="hidden" name="user_id" value="<?php echo $patient['user_id']; ?>">
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($patient['full_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Phone</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>">
        </div>
        
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Gender *</label>
            <select name="gender" required>
                <option value="Male" <?php echo $patient['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo $patient['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo $patient['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Date of Birth *</label>
            <input type="date" name="date_of_birth" value="<?php echo $patient['date_of_birth']; ?>" required>
        </div>
        
        <div class="form-group" style="grid-column: 1 / -1;">
            <label>Address</label>
            <textarea name="address" rows="3"><?php echo htmlspecialchars($patient['address']); ?></textarea>
        </div>
    </div>
    
    <button type="submit" class="btn btn-glass-primary" style="width: 100%; margin-top: 20px;">Save Changes</button>
</form>
<?php
    } else {
        echo '<p>Patient not found.</p>';
    }
}
?>
