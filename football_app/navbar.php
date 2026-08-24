<?php
// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
if ($current_page == 'index') $current_page = 'home';
?>
<header class="navbar">
    <div class="logo-box">
        <img src="assets/logo.png" class="logo" alt="Logo">
        <h2>Pakistan Football <span>Tournament Manager</span></h2>
    </div>

    <div class="hamburger" id="hamburger" aria-label="Toggle navigation">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <nav class="nav-links" id="navLinks">
        <a href="index.php" class="<?php echo $current_page == 'home' ? 'active' : ''; ?>">Home</a>
        <a href="tournament.php" class="<?php echo $current_page == 'tournament' ? 'active' : ''; ?>">Tournaments</a>
        <a href="team.php" class="<?php echo $current_page == 'team' ? 'active' : ''; ?>">Teams</a>
        <a href="players.php" class="<?php echo $current_page == 'players' ? 'active' : ''; ?>">Players</a>
        <a href="matches.php" class="<?php echo $current_page == 'matches' ? 'active' : ''; ?>">Matches</a>
        <div class="nav-cta">
            <a href="logout.php" class="cta-btn">Logout</a>
        </div>
    </nav>
</header>

<script>
// Hamburger menu toggle
document.getElementById('hamburger').addEventListener('click', function() {
    this.classList.toggle('active');
    document.getElementById('navLinks').classList.toggle('active');
});

// Close menu when a link is clicked (mobile)
document.querySelectorAll('.nav-links a').forEach(function(link) {
    link.addEventListener('click', function() {
        document.getElementById('hamburger').classList.remove('active');
        document.getElementById('navLinks').classList.remove('active');
    });
});
</script>
