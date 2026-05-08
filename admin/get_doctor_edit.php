<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Unauthorized');
}

include '../includes/config.php';

if (isset($_GET['id'])) {
    $doctor_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT d.*, u.username, u.email 
                           FROM doctors d 
                           JOIN users u ON d.user_id = u.id 
                           WHERE d.id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
    
    if ($doctor) {
?>
<form method="POST" action="process_edit_doctor.php">
    <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
    <input type="hidden" name="user_id" value="<?php echo $doctor['user_id']; ?>">
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($doctor['full_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Specialization *</label>
            <select name="specialization" required>
                <?php
                $specs = ['Cardiologist','Dermatologist','Endocrinologist','ENT Specialist','Gastroenterologist','General Physician','General Surgeon','Gynecologist','Hematologist','Nephrologist','Neurologist','Neurosurgeon','Obstetrician','Oncologist','Ophthalmologist','Orthopedic Surgeon','Pediatrician','Psychiatrist','Pulmonologist','Radiologist','Rheumatologist','Urologist','Other'];
                foreach ($specs as $sp) {
                    $sel = ($doctor['specialization'] === $sp) ? 'selected' : '';
                    echo "<option $sel>$sp</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Phone</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>">
        </div>
        
        <div class="form-group">
            <label>License Number</label>
            <input type="text" name="license_number" value="<?php echo htmlspecialchars($doctor['license_number']); ?>">
        </div>
        
        <div class="form-group">
            <label>Consultation Fee (Rs.) *</label>
            <input type="number" name="consultation_fee" step="0.01" value="<?php echo $doctor['consultation_fee']; ?>" required>
        </div>
    </div>

    <!-- Hospital assignment -->
    <div class="form-group" style="margin-top:10px;">
        <label style="font-weight:bold;">🏥 Assign Hospitals</label>
        <?php
        // Current hospitals for this doctor
        $cur = $conn->prepare("SELECT hospital_name, is_primary FROM doctor_hospitals WHERE doctor_id = ?");
        $cur->bind_param("i", $doctor_id); $cur->execute();
        $cur_rows = $cur->get_result()->fetch_all(MYSQLI_ASSOC); $cur->close();
        $cur_names   = array_column($cur_rows, 'hospital_name');
        $primary_now = '';
        foreach ($cur_rows as $r) { if ($r['is_primary']) $primary_now = $r['hospital_name']; }

        // All hospitals
        $all_h = $conn->query("SELECT name FROM hospitals ORDER BY name");
        ?>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin:8px 0;">
        <?php while ($h = $all_h->fetch_assoc()):
            $checked = in_array($h['name'], $cur_names) ? 'checked' : ''; ?>
            <label style="display:flex;align-items:center;gap:5px;padding:5px 12px;background:#e8f5e9;border:2px solid #a5d6a7;border-radius:20px;cursor:pointer;font-size:13px;">
                <input type="checkbox" name="hospitals[]" value="<?php echo htmlspecialchars($h['name']); ?>" <?php echo $checked; ?> style="width:auto;margin:0;">
                <?php echo htmlspecialchars($h['name']); ?>
            </label>
        <?php endwhile; ?>
        </div>
        <label style="font-size:13px;font-weight:bold;margin-top:8px;display:block;">⭐ Primary Hospital:</label>
        <select name="primary_hospital" style="padding:8px;border:2px solid #ddd;border-radius:6px;width:100%;margin-top:4px;">
            <option value="">Select primary...</option>
            <?php
            $all_h2 = $conn->query("SELECT name FROM hospitals ORDER BY name");
            while ($h = $all_h2->fetch_assoc()) {
                $sel = ($h['name'] === $primary_now) ? 'selected' : '';
                echo "<option value=\"" . htmlspecialchars($h['name']) . "\" $sel>" . htmlspecialchars($h['name']) . "</option>";
            }
            ?>
        </select>
    </div>

    <button type="submit" class="btn btn-glass-primary" style="width:100%;margin-top:20px;">Save Changes</button>
</form>
<?php
    } else {
        echo '<p>Doctor not found.</p>';
    }
}
?>
