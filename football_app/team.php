<?php
require_once 'config.php';
requireLogin();

$error = "";
$success = "";

// ========== ADD TEAM ==========
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $coach = trim($_POST['coach']) ?: null;
    $tournament_id = (int)$_POST['tournament_id'];

    if ($name === "" || $tournament_id <= 0) {
        $error = "Team name and tournament are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Teams (Name, CoachName, TournamentID) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $coach, $tournament_id);
        if ($stmt->execute()) {
            $success = "Team added successfully!";
        } else {
            $error = "Error adding team: " . $conn->error;
        }
        $stmt->close();
    }
}

// ========== EDIT TEAM ==========
if (isset($_POST['edit'])) {
    $id = (int)$_POST['edit_id'];
    $name = trim($_POST['edit_name']);
    $coach = trim($_POST['edit_coach']) ?: null;
    $tournament_id = (int)$_POST['edit_tournament_id'];

    if ($name === "" || $tournament_id <= 0) {
        $error = "Team name and tournament are required.";
    } else {
        $stmt = $conn->prepare("UPDATE Teams SET Name=?, CoachName=?, TournamentID=? WHERE TeamID=?");
        $stmt->bind_param("ssii", $name, $coach, $tournament_id, $id);
        if ($stmt->execute()) {
            $success = "Team updated successfully!";
        } else {
            $error = "Error updating team.";
        }
        $stmt->close();
    }
}

// ========== DELETE TEAM ==========
if (isset($_GET['del'])) {
    $del_id = (int)$_GET['del'];
    $stmt = $conn->prepare("DELETE FROM Teams WHERE TeamID = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $success = "Team deleted successfully!";
    } else {
        $error = "Error deleting team. It may have associated players or matches.";
    }
    $stmt->close();
    header("Location: team.php" . ($success ? "?success=" . urlencode($success) : "?error=" . urlencode($error)));
    exit();
}

if (isset($_GET['success'])) $success = $_GET['success'];
if (isset($_GET['error'])) $error = $_GET['error'];

// ========== SEARCH ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
if ($search !== "") {
    $like = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT t.*, tr.Name AS TournamentName FROM Teams t LEFT JOIN Tournaments tr ON t.TournamentID = tr.TournamentID WHERE t.Name LIKE ? OR t.CoachName LIKE ? OR tr.Name LIKE ? ORDER BY t.TeamID DESC");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $teams = $stmt->get_result();
    $stmt->close();
} else {
    $teams = $conn->query("SELECT t.*, tr.Name AS TournamentName FROM Teams t LEFT JOIN Tournaments tr ON t.TournamentID = tr.TournamentID ORDER BY t.TeamID DESC");
}

// Get all tournaments for dropdown
$tournaments_list = $conn->query("SELECT TournamentID, Name FROM Tournaments ORDER BY Name");

// Get team for editing
$edit_team = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT t.*, tr.Name AS TournamentName FROM Teams t LEFT JOIN Tournaments tr ON t.TournamentID = tr.TournamentID WHERE t.TeamID = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_team = $result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Management | Pakistan Football Tournament Manager</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include "navbar.php"; ?>

<!-- PAGE TITLE -->
<h1 class="page-title">Team Management</h1>

<!-- ALERTS -->
<?php if ($error !== ""): ?>
    <div class="alert alert-error" style="max-width:600px;margin:10px auto;"><?php echo e($error); ?></div>
<?php endif; ?>
<?php if ($success !== ""): ?>
    <div class="alert alert-success" style="max-width:600px;margin:10px auto;"><?php echo e($success); ?></div>
<?php endif; ?>

<!-- ADD / EDIT TEAM FORM -->
<div class="form-box">
    <?php if ($edit_team): ?>
        <h3>Edit Team</h3>
        <form method="POST" action="">
            <input type="hidden" name="edit_id" value="<?php echo $edit_team['TeamID']; ?>">
            <input type="text" name="edit_name" class="input-field" placeholder="Team Name" required value="<?php echo e($edit_team['Name']); ?>">
            <input type="text" name="edit_coach" class="input-field" placeholder="Coach Name" value="<?php echo e($edit_team['CoachName'] ?? ''); ?>">
            <select name="edit_tournament_id" class="input-field" required>
                <option value="">-- Select Tournament --</option>
                <?php $tlist2 = $conn->query("SELECT TournamentID, Name FROM Tournaments ORDER BY Name"); ?>
                <?php if ($tlist2): while ($t = $tlist2->fetch_assoc()): ?>
                    <option value="<?php echo $t['TournamentID']; ?>" <?php echo ($edit_team['TournamentID'] == $t['TournamentID']) ? 'selected' : ''; ?>>
                        <?php echo e($t['Name']); ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
            <div style="display:flex;gap:15px;margin-top:15px;">
                <button class="btn2" name="edit" style="flex:1;">Update Team</button>
                <a href="team.php" class="del-btn" style="flex:1;text-align:center;padding:16px 20px;">Cancel</a>
            </div>
        </form>
    <?php else: ?>
        <h3>Add New Team</h3>
        <form method="POST" action="">
            <input type="text" name="name" class="input-field" placeholder="Team Name" required>
            <input type="text" name="coach" class="input-field" placeholder="Coach Name">
            <select name="tournament_id" class="input-field" required>
                <option value="">-- Select Tournament --</option>
                <?php if ($tournaments_list): while ($t = $tournaments_list->fetch_assoc()): ?>
                    <option value="<?php echo $t['TournamentID']; ?>"><?php echo e($t['Name']); ?></option>
                <?php endwhile; endif; ?>
            </select>
            <button class="btn2" name="add" style="width:100%;margin-top:15px;">Add Team</button>
        </form>
    <?php endif; ?>
</div>

<!-- SEARCH BAR -->
<div class="search-bar">
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search teams by name, coach or tournament..." value="<?php echo e($search); ?>">
    </form>
</div>

<!-- TEAM TABLE -->
<div class="table-container">
    <table>
        <tr>
            <th>ID</th>
            <th>Team Name</th>
            <th>Coach</th>
            <th>Tournament</th>
            <th>Actions</th>
        </tr>

        <?php if ($teams && $teams->num_rows > 0): ?>
            <?php while ($r = $teams->fetch_assoc()): ?>
            <tr class="row-hover">
                <td><?php echo e($r['TeamID']); ?></td>
                <td><strong><?php echo e($r['Name']); ?></strong></td>
                <td><?php echo e($r['CoachName'] ?? '-'); ?></td>
                <td><?php echo e($r['TournamentName'] ?? 'N/A'); ?></td>
                <td>
                    <div class="action-btns">
                        <a href="?edit=<?php echo $r['TeamID']; ?>" class="edit-btn">Edit</a>
                        <a href="?del=<?php echo $r['TeamID']; ?>" onclick="return confirm('Are you sure you want to delete this team?')" class="del-btn">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" class="no-records">No teams found.</td></tr>
        <?php endif; ?>

    </table>
</div>

<?php include "footer.php"; ?>

</body>
</html>
