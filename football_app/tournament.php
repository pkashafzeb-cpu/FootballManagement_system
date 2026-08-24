<?php
require_once 'config.php';
requireLogin();

$error = "";
$success = "";

// ========== ADD TOURNAMENT ==========
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $start = $_POST['start'] ?: null;
    $end = $_POST['end'] ?: null;
    $location = trim($_POST['location']) ?: null;

    if ($name === "") {
        $error = "Tournament name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Tournaments (Name, StartDate, EndDate, Location) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $start, $end, $location);
        if ($stmt->execute()) {
            $success = "Tournament added successfully!";
        } else {
            $error = "Error adding tournament: " . $conn->error;
        }
        $stmt->close();
    }
}

// ========== EDIT TOURNAMENT ==========
if (isset($_POST['edit'])) {
    $id = (int)$_POST['edit_id'];
    $name = trim($_POST['edit_name']);
    $start = $_POST['edit_start'] ?: null;
    $end = $_POST['edit_end'] ?: null;
    $location = trim($_POST['edit_location']) ?: null;

    if ($name === "") {
        $error = "Tournament name is required.";
    } else {
        $stmt = $conn->prepare("UPDATE Tournaments SET Name=?, StartDate=?, EndDate=?, Location=? WHERE TournamentID=?");
        $stmt->bind_param("ssssi", $name, $start, $end, $location, $id);
        if ($stmt->execute()) {
            $success = "Tournament updated successfully!";
        } else {
            $error = "Error updating tournament.";
        }
        $stmt->close();
    }
}

// ========== DELETE TOURNAMENT ==========
if (isset($_GET['del'])) {
    $del_id = (int)$_GET['del'];
    $stmt = $conn->prepare("DELETE FROM Tournaments WHERE TournamentID = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $success = "Tournament deleted successfully!";
    } else {
        $error = "Error deleting tournament. It may have associated teams or matches.";
    }
    $stmt->close();
    // Redirect to remove GET params
    header("Location: tournament.php" . ($success ? "?success=" . urlencode($success) : "?error=" . urlencode($error)));
    exit();
}

// Check for redirect messages
if (isset($_GET['success'])) $success = $_GET['success'];
if (isset($_GET['error'])) $error = $_GET['error'];

// ========== SEARCH ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
if ($search !== "") {
    $like = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT * FROM Tournaments WHERE Name LIKE ? OR Location LIKE ? ORDER BY StartDate DESC");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $tournaments = $stmt->get_result();
    $stmt->close();
} else {
    $tournaments = $conn->query("SELECT * FROM Tournaments ORDER BY StartDate DESC");
}

// Get tournament for editing
$edit_tournament = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM Tournaments WHERE TournamentID = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_tournament = $result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Management | Pakistan Football Tournament Manager</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include "navbar.php"; ?>

<!-- PAGE TITLE -->
<h1 class="page-title">Tournament Management</h1>

<!-- ALERTS -->
<?php if ($error !== ""): ?>
    <div class="alert alert-error" style="max-width:600px;margin:10px auto;"><?php echo e($error); ?></div>
<?php endif; ?>
<?php if ($success !== ""): ?>
    <div class="alert alert-success" style="max-width:600px;margin:10px auto;"><?php echo e($success); ?></div>
<?php endif; ?>

<!-- ADD / EDIT TOURNAMENT FORM -->
<div class="form-box">
    <?php if ($edit_tournament): ?>
        <h3>Edit Tournament</h3>
        <form method="POST" action="">
            <input type="hidden" name="edit_id" value="<?php echo $edit_tournament['TournamentID']; ?>">
            <input type="text" name="edit_name" class="input-field" placeholder="Tournament Name" required value="<?php echo e($edit_tournament['Name']); ?>">
            <div class="form-row">
                <input type="date" name="edit_start" class="input-field" value="<?php echo e($edit_tournament['StartDate']); ?>">
                <input type="date" name="edit_end" class="input-field" value="<?php echo e($edit_tournament['EndDate']); ?>">
            </div>
            <input type="text" name="edit_location" class="input-field" placeholder="Location" value="<?php echo e($edit_tournament['Location']); ?>">
            <div style="display:flex;gap:15px;margin-top:15px;">
                <button class="btn2" name="edit" style="flex:1;">Update Tournament</button>
                <a href="tournament.php" class="del-btn" style="flex:1;text-align:center;padding:16px 20px;">Cancel</a>
            </div>
        </form>
    <?php else: ?>
        <h3>Add New Tournament</h3>
        <form method="POST" action="">
            <input type="text" name="name" class="input-field" placeholder="Tournament Name" required>
            <div class="form-row">
                <input type="date" name="start" class="input-field" placeholder="Start Date">
                <input type="date" name="end" class="input-field" placeholder="End Date">
            </div>
            <input type="text" name="location" class="input-field" placeholder="Location">
            <button class="btn2" name="add" style="width:100%;margin-top:15px;">Add Tournament</button>
        </form>
    <?php endif; ?>
</div>

<!-- SEARCH BAR -->
<div class="search-bar">
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search tournaments by name or location..." value="<?php echo e($search); ?>">
    </form>
</div>

<!-- TOURNAMENT TABLE -->
<div class="table-container">
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Location</th>
            <th>Actions</th>
        </tr>

        <?php if ($tournaments && $tournaments->num_rows > 0): ?>
            <?php while ($r = $tournaments->fetch_assoc()): ?>
            <tr class="row-hover">
                <td><?php echo e($r['TournamentID']); ?></td>
                <td><strong><?php echo e($r['Name']); ?></strong></td>
                <td><?php echo e($r['StartDate'] ?? '-'); ?></td>
                <td><?php echo e($r['EndDate'] ?? '-'); ?></td>
                <td><?php echo e($r['Location'] ?? '-'); ?></td>
                <td>
                    <div class="action-btns">
                        <a href="?edit=<?php echo $r['TournamentID']; ?>" class="edit-btn">Edit</a>
                        <a href="?del=<?php echo $r['TournamentID']; ?>" onclick="return confirm('Are you sure you want to delete this tournament?')" class="del-btn">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" class="no-records">No tournaments found.</td></tr>
        <?php endif; ?>

    </table>
</div>

<?php include "footer.php"; ?>

</body>
</html>
