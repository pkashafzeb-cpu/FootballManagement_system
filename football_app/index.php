<?php
require_once 'config.php';
requireLogin();

// Get statistics for dashboard
$stats = [
    'tournaments' => 0,
    'teams' => 0,
    'players' => 0,
    'matches' => 0,
    'upcoming' => 0,
    'completed' => 0
];

$result = $conn->query("SELECT COUNT(*) as cnt FROM Tournaments");
if ($row = $result->fetch_assoc()) $stats['tournaments'] = $row['cnt'];

$result = $conn->query("SELECT COUNT(*) as cnt FROM Teams");
if ($row = $result->fetch_assoc()) $stats['teams'] = $row['cnt'];

$result = $conn->query("SELECT COUNT(*) as cnt FROM Players");
if ($row = $result->fetch_assoc()) $stats['players'] = $row['cnt'];

$result = $conn->query("SELECT COUNT(*) as cnt FROM Matches");
if ($row = $result->fetch_assoc()) $stats['matches'] = $row['cnt'];

// Upcoming matches (date >= today)
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM Matches WHERE MatchDate >= CURDATE()");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) $stats['upcoming'] = $row['cnt'];
$stmt->close();

// Completed matches (date < today or scores recorded)
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM Matches WHERE MatchDate < CURDATE()");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) $stats['completed'] = $row['cnt'];
$stmt->close();

// Get upcoming matches with team names
$upcoming_matches = $conn->query("
    SELECT m.MatchID, m.MatchDate, m.Team1Score, m.Team2Score,
           t1.Name AS Team1Name, t2.Name AS Team2Name,
           tr.Name AS TournamentName
    FROM Matches m
    LEFT JOIN Teams t1 ON m.Team1ID = t1.TeamID
    LEFT JOIN Teams t2 ON m.Team2ID = t2.TeamID
    LEFT JOIN Tournaments tr ON m.TournamentID = tr.TournamentID
    ORDER BY m.MatchDate ASC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Pakistan Football Tournament Manager</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include "navbar.php"; ?>

<!-- HERO BANNER -->
<section class="hero-banner">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1 class="hero-title">
            <span class="hero-main-text">Pakistan Football</span>
            <span class="hero-sub-text">Tournament Manager</span>
        </h1>
        <p>Organize tournaments, teams, players & match schedules — all in one place.</p>
        <a href="tournament.php" class="hero-btn">Get Started</a>
    </div>
</section>

<!-- STATISTICS -->
<section class="stats-box" style="margin-top: 40px;">
    <h2 class="page-title" style="margin-top:0;">Dashboard Overview</h2>
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['tournaments']; ?></div>
            <div class="stat-label">Tournaments</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['teams']; ?></div>
            <div class="stat-label">Teams</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['players']; ?></div>
            <div class="stat-label">Players</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['matches']; ?></div>
            <div class="stat-label">Matches</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['upcoming']; ?></div>
            <div class="stat-label">Upcoming</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['completed']; ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>
</section>

<!-- DASHBOARD CARDS -->
<section class="dashboard">
    <div class="dash-grid">

        <div class="dash-card">
            <img src="assets/tournament.jfif" alt="Tournaments">
            <h3>Tournaments</h3>
            <p>Create and manage tournament records. Set dates, locations and track all events.</p>
            <a href="tournament.php" class="dash-btn">Manage Tournaments</a>
        </div>

        <div class="dash-card">
            <img src="assets/team.jfif" alt="Teams">
            <h3>Teams</h3>
            <p>Manage all registered football teams. Assign coaches and link teams to tournaments.</p>
            <a href="team.php" class="dash-btn">Manage Teams</a>
        </div>

        <div class="dash-card">
            <img src="assets/players.jfif" alt="Players">
            <h3>Players</h3>
            <p>View and update player information. Track positions, ages and team assignments.</p>
            <a href="players.php" class="dash-btn">Manage Players</a>
        </div>

        <div class="dash-card">
            <img src="assets/matches.jpg" alt="Matches">
            <h3>Matches</h3>
            <p>Manage fixtures, schedules & results. Track scores and upcoming games.</p>
            <a href="matches.php" class="dash-btn">Manage Matches</a>
        </div>

    </div>
</section>

<!-- RECENT MATCHES -->
<?php if ($upcoming_matches && $upcoming_matches->num_rows > 0): ?>
<section class="table-container" style="margin-top: 20px;">
    <h2 class="page-title" style="margin-top:0; font-size: 28px;">Recent & Upcoming Matches</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Tournament</th>
            <th>Team 1</th>
            <th>Score</th>
            <th>Team 2</th>
        </tr>
        <?php while ($m = $upcoming_matches->fetch_assoc()): ?>
        <tr class="row-hover">
            <td><?php echo e($m['MatchDate'] ?? 'TBD'); ?></td>
            <td><?php echo e($m['TournamentName'] ?? 'N/A'); ?></td>
            <td><strong><?php echo e($m['Team1Name'] ?? 'Team ' . $m['Team1ID']); ?></strong></td>
            <td class="score-display">
                <?php echo e($m['Team1Score'] ?? 0); ?> <span class="vs">vs</span> <?php echo e($m['Team2Score'] ?? 0); ?>
            </td>
            <td><strong><?php echo e($m['Team2Name'] ?? 'Team ' . $m['Team2ID']); ?></strong></td>
        </tr>
        <?php endwhile; ?>
    </table>
</section>
<?php endif; ?>

<?php include "footer.php"; ?>

</body>
</html>
