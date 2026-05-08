<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo '<p style="color:red;">Unauthorized</p>'; exit();
}
include '../includes/config.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { echo '<p style="color:red;">Invalid schedule ID.</p>'; exit(); }

// Load schedule
$stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$s = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$s) { echo '<p style="color:red;">Schedule not found.</p>'; exit(); }

// Load hospitals into array so we can reuse
$hosp_result = $conn->query("SELECT name FROM hospitals ORDER BY name");
$hosp_list = [];
while ($h = $hosp_result->fetch_assoc()) {
    $hosp_list[] = $h['name'];
}

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
?>
<form method="POST" action="process_edit_schedule.php">
    <input type="hidden" name="schedule_id" value="<?php echo $s['id']; ?>">
    <input type="hidden" name="doctor_id"   value="<?php echo $s['doctor_id']; ?>">

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">

        <div class="form-group">
            <label>Day of Week *</label>
            <select name="day_of_week" required style="width:100%; padding:10px; border:2px solid #ddd; border-radius:6px;">
                <?php foreach ($days as $day): ?>
                <option value="<?php echo $day; ?>" <?php echo $s['day_of_week']==$day ? 'selected':''; ?>>
                    <?php echo $day; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Hospital *</label>
            <select name="hospital_name" required style="width:100%; padding:10px; border:2px solid #ddd; border-radius:6px;">
                <option value="">Select hospital...</option>
                <?php foreach ($hosp_list as $name): ?>
                <option value="<?php echo htmlspecialchars($name); ?>"
                    <?php echo $s['hospital_name']==$name ? 'selected':''; ?>>
                    <?php echo htmlspecialchars($name); ?>
                </option>
                <?php endforeach; ?>
                <?php
                // If current hospital not in list, still show it selected
                if ($s['hospital_name'] && !in_array($s['hospital_name'], $hosp_list)):
                ?>
                <option value="<?php echo htmlspecialchars($s['hospital_name']); ?>" selected>
                    <?php echo htmlspecialchars($s['hospital_name']); ?>
                </option>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Start Time *</label>
            <input type="time" name="start_time" value="<?php echo htmlspecialchars($s['start_time']); ?>"
                   required style="width:100%; padding:10px; border:2px solid #ddd; border-radius:6px;">
        </div>

        <div class="form-group">
            <label>End Time *</label>
            <input type="time" name="end_time" value="<?php echo htmlspecialchars($s['end_time']); ?>"
                   required style="width:100%; padding:10px; border:2px solid #ddd; border-radius:6px;">
        </div>

        <div class="form-group">
            <label>Max Patients</label>
            <input type="number" name="max_patients" value="<?php echo intval($s['max_patients']); ?>"
                   min="1" max="100"
                   style="width:100%; padding:10px; border:2px solid #ddd; border-radius:6px;">
        </div>

        <div class="form-group">
            <label>Slot Duration (minutes)</label>
            <select name="slot_duration" style="width:100%; padding:10px; border:2px solid #ddd; border-radius:6px;">
                <?php foreach ([15,30,45,60] as $dur): ?>
                <option value="<?php echo $dur; ?>" <?php echo $s['slot_duration']==$dur ? 'selected':''; ?>>
                    <?php echo $dur; ?> minutes
                </option>
                <?php endforeach; ?>
            </select>
        </div>

    </div>

    <div style="display:flex; gap:10px; margin-top:20px;">
        <button type="submit" class="btn btn-glass-primary" style="flex:1; padding:13px; font-size:15px;">
            ✅ Save Changes
        </button>
        <button type="button" class="btn btn-danger" style="padding:13px 20px;"
                onclick="document.getElementById('editModal').classList.remove('active')">
            Cancel
        </button>
    </div>
</form>
