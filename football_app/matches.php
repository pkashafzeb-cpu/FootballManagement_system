<?php
require_once 'config.php';
requireLogin();

$error = "";
$success = "";

// ========== ADD MATCH ==========
if (isset($_POST['add'])) {
    $tournament_id = !empty($_POST['tournament_id']) ? (int)$_POST['tournament_id'] : null;
    $team1_id = (int)$_POST['team1_id'];
    $team2_id = (int)$_POST['team2_id'];
    $team1_score = isset($_POST['team1_score']) && $_POST['team1_score'] !== '' ? (int)$_POST['team1_score'] : 0;
    $team2_score = isset($_POST['team2_score']) && $_POST['team2_score'] !== '' ? (int)$_POST['team2_score'] : 0;
    $match_date = !empty($_POST['match_date']) ? $_POST['match_date'] : null;

    if ($team1_id <= 0 || $team2_id <= 0) {
        $error = "Both teams are required.";
    } elseif ($team1_id === $team2_id) {
        $error = "Team 1 and Team 2 cannot be the same.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Matches (TournamentID, Team1ID, Team2ID, Team1Score, Team2Score, MatchDate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiss", $tournament_id, $team1_id, $team2_id, $team1_score, $team2_score, $match_date);
        if ($stmt->execute()) {
            $success = "Match added successfully!";
        } else {
            $error = "Error adding match: " . $conn->error;
        }
        $stmt->close();
    }
}

// ========== EDIT MATCH ==========
if (isset($_POST['edit'])) {
    $id = (int)$_POST['edit_id'];
    $tournament_id = !empty($_POST['edit_tournament_id']) ? (int)$_POST['edit_tournament_id'] : null;
    $team1_id = (int)$_POST['edit_team1_id'];
    $team2_id = (int)$_POST['edit_team2_id'];
    $team1_score = isset($_POST['edit_team1_score']) ? (int)$_POST['edit_team1_score'] : 0;
    $team2_score = isset($_POST['edit_team2_score']) ? (int)$_POST['edit_team2_score'] : 0;
    $match_date = !empty($_POST['edit_match_date']) ? $_POST['edit_match_date'] : null;

    if ($team1_id <= 0 || $team2_id <= 0) {
        $error = "Both teams are required.";
    } elseif ($team1_id === $team2_id) {
        $error = "Team 1 and Team 2 cannot be the same.";
    } else {
        $stmt = $conn->prepare("UPDATE Matches SET TournamentID=?, Team1ID=?, Team2ID=?, Team1Score=?, Team2Score=?, MatchDate=? WHERE MatchID=?");
        $stmt->bind_param("iiiissi", $tournament_id, $team1_id, $team2_id, $team1_score, $team2_score, $match_date, $id);
        if ($stmt->execute()) {
            $success = "Match updated successfully!";
        } else {
            $error = "Error updating match.";
        }
        $stmt->close();
    }
}

// ========== DELETE MATCH ==========
if (isset($_GET['del'])) {
    $del_id = (int)$_GET['del'];
    $stmt = $conn->prepare("DELETE FROM Matches WHERE MatchID = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $success = "Match deleted successfully!";
    } else {
        $error = "Error deleting match.";
    }
    $stmt->close();
    header("Location: matches.php" . ($success ? "?success=" . urlencode($success) : "?error=" . urlencode($error)));
    exit();
}

if (isset($_GET['success'])) $success = $_GET['success'];
if (isset($_GET['error'])) $error = $_GET['error'];

// ========== SEARCH ==========
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
if ($search !== "") {
    $like = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT m.*, t1.Name AS Team1Name, t2.Name AS Team2Name, tr.Name AS TournamentName
        FROM Matches m
        LEFT JOIN Teams t1 ON m.Team1ID = t1.TeamID
        LEFT JOIN Teams t2 ON m.Team2ID = t2.TeamID
        LEFT JOIN Tournaments tr ON m.TournamentID = tr.TournamentID
        WHERE t1.Name LIKE ? OR t2.Name LIKE ? OR tr.Name LIKE ? OR m.Location LIKE ?
        ORDER BY m.MatchDate DESC");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $matches = $stmt->get_result();
    $stmt->close();
} else {
    $matches = $conn->query("SELECT m.*, t1.Name AS Team1Name, t2.Name AS Team2Name, tr.Name AS TournamentName
        FROM Matches m
        LEFT JOIN Teams t1 ON m.Team1ID = t1.TeamID
        LEFT JOIN Teams t2 ON m.Team2ID = t2.TeamID
        LEFT JOIN Tournaments tr ON m.TournamentID = tr.TournamentID
        ORDER BY m.MatchDate DESC");
}

// Get all teams and tournaments for dropdowns
$teams_list = $conn->query("SELECT t.TeamID, t.Name, tr.Name AS TournamentName FROM Teams t LEFT JOIN Tournaments tr ON t.TournamentID = tr.TournamentID ORDER BY t.Name");
$tournaments_list = $conn->query("SELECT TournamentID, Name FROM Tournaments ORDER BY Name");

// Get match for editing
$edit_match = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT m.*, t1.Name AS Team1Name, t2.Name AS Team2Name, tr.Name AS TournamentName
        FROM Matches m
        LEFT JOIN Teams t1 ON m.Team1ID = t1.TeamID
        LEFT JOIN Teams t2 ON m.Team2ID = t2.TeamID
        LEFT JOIN Tournaments tr ON m.TournamentID = tr.TournamentID
        WHERE m.MatchID = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_match = $result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Management | Pakistan Football Tournament Manager</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include "navbar.php"; ?>

<!-- PAGE TITLE -->
<h1 class="page-title">Match Management</h1>

<!-- ALERTS -->
<?php if ($error !== ""): ?>
    <div class="alert alert-error" style="max-width:600px;margin:10px auto;"><?php echo e($error); ?></div>
<?php endif; ?>
<?php if ($success !== ""): ?>
    <div class="alert alert-success" style="max-width:600px;margin:10px auto;"><?php echo e($success); ?></div>
<?php endif; ?>

<!-- ADD / EDIT MATCH FORM -->
<div class="form-box">
    <?php if ($edit_match): ?>
        <h3>Edit Match</h3>
        <form method="POST" action="">
            <input type="hidden" name="edit_id" value="<?php echo $edit_match['MatchID']; ?>">
            <select name="edit_tournament_id" class="input-field">
                <option value="">-- Select Tournament --</option>
                <?php $tlist2 = $conn->query("SELECT TournamentID, Name FROM Tournaments ORDER BY Name"); ?>
                <?php if ($tlist2): while ($t = $tlist2->fetch_assoc()): ?>
                    <option value="<?php echo $t['TournamentID']; ?>" <?php echo ($edit_match['TournamentID'] == $t['TournamentID']) ? 'selected' : ''; ?>>
                        <?php echo e($t['Name']); ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
            <div class="form-row">
                <select name="edit_team1_id" class="input-field" required>
                    <option value="">-- Team 1 --</option>
                    <?php $tlist3 = $conn->query("SELECT TeamID, Name FROM Teams ORDER BY Name"); ?>
                    <?php if ($tlist3): while ($t = $tlist3->fetch_assoc()): ?>
                        <option value="<?php echo $t['TeamID']; ?>" <?php echo ($edit_match['Team1ID'] == $t['TeamID']) ? 'selected' : ''; ?>>
                            <?php echo e($t['Name']); ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
                <select name="edit_team2_id" class="input-field" required>
                    <option value="">-- Team 2 --</option>
                    <?php $tlist4 = $conn->query("SELECT TeamID, Name FROM Teams ORDER BY Name"); ?>
                    <?php if ($tlist4): while ($t = $tlist4->fetch_assoc()): ?>
                        <option value="<?php echo $t['TeamID']; ?>" <?php echo ($edit_match['Team2ID'] == $t['TeamID']) ? 'selected' : ''; ?>>
                            <?php echo e($t['Name']); ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div class="form-row">
                <input type="number" name="edit_team1_score" class="input-field" placeholder="Team 1 Score" min="0" value="<?php echo e($edit_match['Team1Score'] ?? 0); ?>">
                <input type="number" name="edit_team2_score" class="input-field" placeholder="Team 2 Score" min="0" value="<?php echo e($edit_match['Team2Score'] ?? 0); ?>">
            </div>
            <input type="date" name="edit_match_date" class="input-field" value="<?php echo e($edit_match['MatchDate'] ?? ''); ?>">
            <div style="display:flex;gap:15px;margin-top:15px;">
                <button class="btn2" name="edit" style="flex:1;">Update Match</button>
                <a href="matches.php" class="del-btn" style="flex:1;text-align:center;padding:16px 20px;">Cancel</a>
            </div>
        </form>
    <?php else: ?>
        <h3>Add New Match</h3>
        <form method="POST" action="">
            <select name="tournament_id" class="input-field">
                <option value="">-- Select Tournament --</option>
                <?php if ($tournaments_list): while ($t = $tournaments_list->fetch_assoc()): ?>
                    <option value="<?php echo $t['TournamentID']; ?>"><?php echo e($t['Name']); ?></option>
                <?php endwhile; endif; ?>
            </select>
            <div class="form-row">
                <select name="team1_id" class="input-field" required>
                    <option value="">-- Team 1 --</option>
                    <?php if ($teams_list): while ($t = $teams_list->fetch_assoc()): ?>
                        <option value="<?php echo $t['TeamID']; ?>">
                            <?php echo e($t['Name']); ?> (<?php echo e($t['TournamentName'] ?? 'N/A'); ?>)
                        </option>
                    <?php endwhile; endif; ?>
                </select>
                <select name="team2_id" class="input-field" required>
                    <option value="">-- Team 2 --</option>
                    <?php $teams_list2 = $conn->query("SELECT t.TeamID, t.Name, tr.Name AS TournamentName FROM Teams t LEFT JOIN Tournaments tr ON t.TournamentID = tr.TournamentID ORDER BY t.Name"); ?>
                    <?php if ($teams_list2): while ($t = $teams_list2->fetch_assoc()): ?>
                        <option value="<?php echo $t['TeamID']; ?>">
                            <?php echo e($t['Name']); ?> (<?php echo e($t['TournamentName'] ?? 'N/A'); ?>)
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div class="form-row">
                <input type="number" name="team1_score" class="input-field" placeholder="Team 1 Score" min="0" value="0">
                <input type="number" name="team2_score" class="input-field" placeholder="Team 2 Score" min="0" value="0">
            </div>
            <input type="date" name="match_date" class="input-field">
            <button class="btn2" name="add" style="width:100%;margin-top:15px;">Add Match</button>
        </form>
    <?php endif; ?>
</div>

<!-- SEARCH BAR -->
<div class="search-bar">
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search matches by team, tournament or location..." value="<?php echo e($search); ?>">
    </form>
</div>

<!-- MATCH TABLE -->
<div class="table-container">
    <table>
        <tr>
            <th>ID</th>
            <th>Tournament</th>
            <th>Team 1</th>
            <th>Score</th>
            <th>Team 2</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>

        <?php if ($matches && $matches->num_rows > 0): ?>
            <?php while ($r = $matches->fetch_assoc()): ?>
            <tr class="row-hover">
                <td><?php echo e($r['MatchID']); ?></td>
                <td><?php echo e($r['TournamentName'] ?? 'N/A'); ?></td>
                <td><strong><?php echo e($r['Team1Name'] ?? 'Team ' . $r['Team1ID']); ?></strong></td>
                <td class="score-display">
                    <?php echo e($r['Team1Score'] ?? 0); ?> <span class="vs">vs</span> <?php echo e($r['Team2Score'] ?? 0); ?>
                </td>
                <td><strong><?php echo e($r['Team2Name'] ?? 'Team ' . $r['Team2ID']); ?></strong></td>
                <td><?php echo e($r['MatchDate'] ?? 'TBD'); ?></td>
                <td>
                    <div class="action-btns">
                        <a href="?edit=<?php echo $r['MatchID']; ?>" class="edit-btn">Edit</a>
                        <a href="?del=<?php echo $r['MatchID']; ?>" onclick="return confirm('Are you sure you want to delete this match?')" class="del-btn">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" class="no-records">No matches found.</td></tr>
        <?php endif; ?>

    </table>
</div>

<?php include "footer.php"; ?>

</body>
</html>
