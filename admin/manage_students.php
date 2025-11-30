<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_subject'])) {
        $subject_name = sanitize($_POST['subject_name']);
        $subject_code = sanitize($_POST['subject_code']);
        $department_id = $_POST['department_id'] ? intval($_POST['department_id']) : NULL;
        $year = intval($_POST['year']);
        $semester = intval($_POST['semester']);
        $credits = intval($_POST['credits']);
        $total_marks = intval($_POST['total_marks']);
        $passing_marks = intval($_POST['passing_marks']);
        
        $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, department_id, year, semester, credits, total_marks, passing_marks, is_active) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssiiiiii", $subject_name, $subject_code, $department_id, $year, $semester, $credits, $total_marks, $passing_marks);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Subject added successfully!";
        } else {
            $_SESSION['error'] = "❌ Error adding subject: " . $conn->error;
        }
        header("Location: manage_subjects.php");
        exit();
    }
    
    if (isset($_POST['update_subject'])) {
        $subject_id = intval($_POST['subject_id']);
        $subject_name = sanitize($_POST['subject_name']);
        $subject_code = sanitize($_POST['subject_code']);
        $department_id = $_POST['department_id'] ? intval($_POST['department_id']) : NULL;
        $year = intval($_POST['year']);
        $semester = intval($_POST['semester']);
        $credits = intval($_POST['credits']);
        $total_marks = intval($_POST['total_marks']);
        $passing_marks = intval($_POST['passing_marks']);
        
        $stmt = $conn->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, department_id = ?, 
                               year = ?, semester = ?, credits = ?, total_marks = ?, passing_marks = ? 
                               WHERE id = ?");
        $stmt->bind_param("ssiiiiiii", $subject_name, $subject_code, $department_id, $year, $semester, 
                         $credits, $total_marks, $passing_marks, $subject_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Subject updated successfully!";
        } else {
            $_SESSION['error'] = "❌ Error updating subject!";
        }
        header("Location: manage_subjects.php");
        exit();
    }
    
    if (isset($_POST['delete_subject'])) {
        $subject_id = intval($_POST['subject_id']);
        
        if ($conn->query("UPDATE subjects SET is_active = 0 WHERE id = $subject_id")) {
            $_SESSION['success'] = "✅ Subject deleted successfully!";
        } else {
            $_SESSION['error'] = "❌ Error deleting subject!";
        }
        header("Location: manage_subjects.php");
        exit();
    }
}

// Get all subjects
$subjects_query = "SELECT s.*, d.dept_name 
                   FROM subjects s
                   LEFT JOIN departments d ON s.department_id = d.id
                   WHERE s.is_active = 1
                   ORDER BY s.year, s.semester, d.dept_name, s.subject_name";
$subjects = $conn->query($subjects_query);

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");

// Get subject for editing if ID is provided
$edit_subject = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_subject = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - Admin</title>
    <link rel="icon" href="../Nit_logo.png" type="image/png" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: rgba(26, 31, 58, 0.95);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        .navbar h1 { color: white; font-size: 24px; }
        .navbar a {
            color: white;
            text-decoration: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 10px 20px;
            border-radius: 10px;
            margin-left: 10px;
        }
        .main-content { padding: 40px; max-width: 1600px; margin: 0 auto; }
        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        h2 {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #667eea;
            outline: none;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        thead th {
            padding: 15px;
            color: white;
            text-align: left;
            font-weight: 600;
        }
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        tbody td {
            padding: 15px;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📚 Manage Subjects</h1>
        <div>
            <a href="manage_subject_teachers.php">👨‍🏫 Assign Teachers</a>
            <a href="index.php">🏠 Dashboard</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="container">
            <h2><?php echo $edit_subject ? '✏️ Edit Subject' : '➕ Add New Subject'; ?></h2>
            
            <form method="POST">
                <?php if ($edit_subject): ?>
                    <input type="hidden" name="subject_id" value="<?php echo $edit_subject['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Subject Code *</label>
                        <input type="text" name="subject_code" required 
                               value="<?php echo $edit_subject['subject_code'] ?? ''; ?>"
                               placeholder="e.g., CS301">
                    </div>
                    
                    <div class="form-group">
                        <label>Subject Name *</label>
                        <input type="text" name="subject_name" required 
                               value="<?php echo $edit_subject['subject_name'] ?? ''; ?>"
                               placeholder="e.g., Data Structures">
                    </div>
                    
                    <div class="form-group">
                        <label>Department (Optional for common subjects)</label>
                        <select name="department_id">
                            <option value="">-- Common Subject (All Departments) --</option>
                            <?php 
                            $departments->data_seek(0);
                            while ($dept = $departments->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $dept['id']; ?>"
                                        <?php echo (isset($edit_subject['department_id']) && $edit_subject['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['dept_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Year *</label>
                        <select name="year" required>
                            <?php for($i=1; $i<=4; $i++): ?>
                                <option value="<?php echo $i; ?>"
                                        <?php echo (isset($edit_subject['year']) && $edit_subject['year'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?><?php echo ($i==1)?'st':(($i==2)?'nd':(($i==3)?'rd':'th')); ?> Year
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Semester *</label>
                        <select name="semester" required>
                            <?php for($i=1; $i<=8; $i++): ?>
                                <option value="<?php echo $i; ?>"
                                        <?php echo (isset($edit_subject['semester']) && $edit_subject['semester'] == $i) ? 'selected' : ''; ?>>
                                    Semester <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Credits *</label>
                        <input type="number" name="credits" required min="1" max="10"
                               value="<?php echo $edit_subject['credits'] ?? 3; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Total Marks *</label>
                        <input type="number" name="total_marks" required min="1" max="200"
                               value="<?php echo $edit_subject['total_marks'] ?? 100; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Passing Marks *</label>
                        <input type="number" name="passing_marks" required min="1" max="100"
                               value="<?php echo $edit_subject['passing_marks'] ?? 40; ?>">
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="<?php echo $edit_subject ? 'update_subject' : 'add_subject'; ?>" class="btn btn-primary">
                        <?php echo $edit_subject ? '💾 Update Subject' : '➕ Add Subject'; ?>
                    </button>
                    <?php if ($edit_subject): ?>
                        <a href="manage_subjects.php" class="btn btn-secondary">❌ Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="container">
            <h2>📋 All Subjects</h2>
            
            <?php if ($subjects->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Department</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Credits</th>
                                <th>Marks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subjects->data_seek(0);
                            while ($subject = $subjects->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($subject['subject_code']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($subject['dept_name'] ?? 'Common'); ?></td>
                                <td>Year <?php echo $subject['year']; ?></td>
                                <td>Sem <?php echo $subject['semester']; ?></td>
                                <td><?php echo $subject['credits']; ?></td>
                                <td><?php echo $subject['total_marks']; ?> (Pass: <?php echo $subject['passing_marks']; ?>)</td>
                                <td>
                                    <a href="?edit_id=<?php echo $subject['id']; ?>" class="btn btn-primary btn-sm">✏️ Edit</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this subject?');">
                                        <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                        <button type="submit" name="delete_subject" class="btn btn-danger btn-sm">🗑️ Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #999;">
                    <p style="font-size: 48px; margin-bottom: 20px;">📚</p>
                    <p>No subjects found. Start by adding your first subject!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>