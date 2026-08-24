<?php
require_once 'config.php';
requireLogin();

$error = "";
$success = "";

// ========== ADD PLAYER ==========
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $age_raw = trim($_POST['age']);
    $position = trim($_POST['position']) ?: null;
    $team_id = (int)$_POST['team_id'];

    if ($name === "" || $team_id <= 0) {
        $error = "Player name and team are required.";
    } else {
        // Age validation
        $age = null;
        if ($age_raw !== "") {
            if (!ctype_digit($age_raw) || (int)$age_raw < 16) {
                $error = "Age must be a valid number (minimum 16).";
            } else {
                $age = (int)$age_raw;
            }
        }

        if ($error === "") {
            $stmt = $conn->prepare("INSERT INTO Players (Name, Age, Position, TeamID) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sisi", $name, $age, $position, $team_id);
            if ($stmt->execute()) {
                $success = "Player added successfully!";
            } else {
                $error = "Error adding player: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// ========== EDIT PLAYER ==========
if (isset($_POST['edit'])) {
    $id = (int)$_POST['edit_id'];
    $name = trim($_POST['edit_name']);
    $age_raw = trim($_POST['edit_age']);
    $position = trim($_POST['edit_position']) ?: null;
    $team_id = (int)$_POST['edit_team_id'];

    if ($name === "" || $team_id <= 0) {
        $error = "Player name and team are required.";
    } else {
        $age = null;
        if ($age_raw !== "") {
            if (!ctype_digit($age_raw) || (int)$age_raw < 16) {
                $error = "Age must be a valid number (minimum 16).";
            } else {
                $age = (int)$age_raw;
            }
        }

        if ($error === "") {
            $stmt = $conn->prepare("UPDATE Players SET Name=?, Age=?, Position=?, TeamID=? WHERE PlayerID=?");
            $stmt->bind_param("sisii", $name, $age, $position, $team_id, $id);
            if ($stmt->execute()) {
                $success = "Player updated successfully!";
            } else {
                $error = "Error updating player.";
            }
            $stmt->close();
        }
    }
}

// ========== DELETE PLAYER ==========
if (isset($_GET['del'])) {
    $del_id = (int)$_GET['del'];
    $stmt = $conn->prepare("DELETE FROM Players WHERE PlayerID = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $success = "Player deleted successfully!";
    } else {
        $error = "Error deleting player.";
    }
    $stmt->close();
    header("Location: players.php" . ($success ? "?success=" . urlencode($success) : "?error=" . urlencode($error)));
    exit();
}

if (isset($_GET['success'])) $success = $_GET['success'];
if (isset($_GET['error'])) $error = $_GET['error'];

// ========== SEARCH ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
if ($search !== "") {
    $like = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT p.*, t.Name AS TeamName FROM Players p LEFT JOIN Teams t ON p.TeamID = t.TeamID WHERE p.Name LIKE ? OR p.Position LIKE ? OR t.Name LIKE ? ORDER BY p.PlayerID DESC");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $players = $stmt->get_result();
    $stmt->close();
} else {
    $players = $conn->query("SELECT p.*, t.Name AS TeamName FROM Players p LEFT JOIN Teams t ON p.TeamID = t.TeamID ORDER BY p.PlayerID DESC");
}

// Get all teams for dropdown
$teams_list = $conn->query("SELECT t.TeamID, t.Name, tr.Name AS TournamentName FROM Teams t LEFT JOIN Tournaments tr ON t.TournamentID = tr.TournamentID ORDER BY t.Name");

// Get player for editing
$edit_player = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT p.*, t.Name AS TeamName FROM Players p LEFT JOIN Teams t ON p.TeamID = t.TeamID WHERE p.PlayerID = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_player = $result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Management | Pakistan Football Tournament Manager</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include "navbar.php"; ?>

<!-- PAGE TITLE -->
<h1 class="page-title">Player Management</h1>

<!-- ALERTS -->
<?php if ($error !== ""): ?>
    <div class="alert alert-error" style="max-width:600px;margin:10px auto;"><?php echo e($error); ?></div>
<?php endif; ?>
<?php if ($success !== ""): ?>
    <div class="alert alert-success" style="max-width:600px;margin:10px auto;"><?php echo e($success); ?></div>
<?php endif; ?>

<!-- ADD / EDIT PLAYER FORM -->
<div class="form-box">
    <?php if ($edit_player): ?>
        <h3>Edit Player</h3>
        <form method="POST" action="">
            <input type="hidden" name="edit_id" value="<?php echo $edit_player['PlayerID']; ?>">
            <input type="text" name="edit_name" class="input-field" placeholder="Player Name" required value="<?php echo e($edit_player['Name']); ?>">
            <div class="form-row">
                <input type="number" name="edit_age" class="input-field" placeholder="Age (min 16)" min="16" value="<?php echo e($edit_player['Age'] ?? ''); ?>">
                <input type="text" name="edit_position" class="input-field" placeholder="Position (e.g. Forward)" value="<?php echo e($edit_player['Position'] ?? ''); ?>">
            </div>
            <select name="edit_team_id" class="input-field" required>
                <option value="">-- Select Team --</option>
                <?php $tlist2 = $conn->query("SELECT t.TeamID, t.Name, tr.Name AS TournamentName FROM Teams t LEFT JOIN Tournaments tr ON t.TournamentID = tr.TournamentID ORDER BY t.Name"); ?>
                <?php if ($tlist2): while ($t = $tlist2->fetch_assoc()): ?>
                    <option value="<?php echo $t['TeamID']; ?>" <?php echo ($edit_player['TeamID'] == $t['TeamID']) ? 'selected' : ''; ?>>
                        <?php echo e($t['Name']); ?> (<?php echo e($t['TournamentName'] ?? 'N/A'); ?>)
                    </option>
                <?php endwhile; endif; ?>
            </select>
            <div style="display:flex;gap:15px;margin-top:15px;">
                <button class="btn2" name="edit" style="flex:1;">Update Player</button>
                <a href="players.php" class="del-btn" style="flex:1;text-align:center;padding:16px 20px;">Cancel</a>
            </div>
        </form>
    <?php else: ?>
        <h3>Add New Player</h3>
        <form method="POST" action="" id="add-player-form">
            <input type="text" name="name" class="input-field" placeholder="Player Name" required>
            <div class="form-row">
                <input type="number" name="age" class="input-field" placeholder="Age (min 16)" min="16">
                <input type="text" name="position" class="input-field" placeholder="Position (e.g. Forward, Midfielder)">
            </div>
            <select name="team_id" class="input-field" required>
                <option value="">-- Select Team --</option>
                <?php if ($teams_list): while ($t = $teams_list->fetch_assoc()): ?>
                    <option value="<?php echo $t['TeamID']; ?>">
                        <?php echo e($t['Name']); ?> (<?php echo e($t['TournamentName'] ?? 'N/A'); ?>)
                    </option>
                <?php endwhile; endif; ?>
            </select>
            <button class="btn2" name="add" style="width:100%;margin-top:15px;">Add Player</button>
        </form>
    <?php endif; ?>
</div>

<!-- SEARCH BAR -->
<div class="search-bar">
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search players by name, position or team..." value="<?php echo e($search); ?>">
    </form>
</div>

<!-- PLAYER TABLE -->
<div class="table-container">
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Age</th>
            <th>Position</th>
            <th>Team</th>
            <th>Actions</th>
        </tr>

        <?php if ($players && $players->num_rows > 0): ?>
            <?php while ($r = $players->fetch_assoc()): ?>
            <tr class="row-hover">
                <td><?php echo e($r['PlayerID']); ?></td>
                <td><strong><?php echo e($r['Name']); ?></strong></td>
                <td><?php echo ($r['Age'] !== null) ? e($r['Age']) : '-'; ?></td>
                <td><?php echo e($r['Position'] ?? '-'); ?></td>
                <td><?php echo e($r['TeamName'] ?? 'N/A'); ?></td>
                <td>
                    <div class="action-btns">
                        <a href="?edit=<?php echo $r['PlayerID']; ?>" class="edit-btn">Edit</a>
                        <a href="?del=<?php echo $r['PlayerID']; ?>" onclick="return confirm('Are you sure you want to delete this player?')" class="del-btn">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" class="no-records">No players found.</td></tr>
        <?php endif; ?>

    </table>
</div>

<?php include "footer.php"; ?>

</body>
</html>
