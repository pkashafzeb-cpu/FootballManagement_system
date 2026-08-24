-- =====================================================
-- Football Tournament Management System
-- Database: FootballTournamentDB
-- Author: Parkha
-- Description: Complete database with tables, views,
--              stored procedures, and sample data
-- =====================================================

CREATE DATABASE IF NOT EXISTS FootballTournamentDB;
USE FootballTournamentDB;

-- =====================================================
-- TABLES
-- =====================================================

-- 1. Users Table (for authentication)
CREATE TABLE IF NOT EXISTS Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL
);

-- 2. Tournaments Table
CREATE TABLE IF NOT EXISTS Tournaments (
    TournamentID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    StartDate DATE,
    EndDate DATE,
    Location VARCHAR(100)
);

-- 3. Teams Table
CREATE TABLE IF NOT EXISTS Teams (
    TeamID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    CoachName VARCHAR(100),
    TournamentID INT,
    FOREIGN KEY (TournamentID) REFERENCES Tournaments(TournamentID) ON DELETE SET NULL
);

-- 4. Players Table
CREATE TABLE IF NOT EXISTS Players (
    PlayerID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Age INT,
    Position VARCHAR(50),
    TeamID INT,
    FOREIGN KEY (TeamID) REFERENCES Teams(TeamID) ON DELETE SET NULL
);

-- 5. Matches Table
CREATE TABLE IF NOT EXISTS Matches (
    MatchID INT AUTO_INCREMENT PRIMARY KEY,
    TournamentID INT,
    Team1ID INT NOT NULL,
    Team2ID INT NOT NULL,
    Team1Score INT DEFAULT 0,
    Team2Score INT DEFAULT 0,
    MatchDate DATE,
    FOREIGN KEY (TournamentID) REFERENCES Tournaments(TournamentID) ON DELETE SET NULL,
    FOREIGN KEY (Team1ID) REFERENCES Teams(TeamID) ON DELETE CASCADE,
    FOREIGN KEY (Team2ID) REFERENCES Teams(TeamID) ON DELETE CASCADE
);

-- 6. Results Table (for standings/leaderboard)
CREATE TABLE IF NOT EXISTS Results (
    ResultID INT AUTO_INCREMENT PRIMARY KEY,
    TournamentID INT,
    TeamID INT,
    Wins INT DEFAULT 0,
    Losses INT DEFAULT 0,
    Draws INT DEFAULT 0,
    Points INT DEFAULT 0,
    FOREIGN KEY (TournamentID) REFERENCES Tournaments(TournamentID) ON DELETE CASCADE,
    FOREIGN KEY (TeamID) REFERENCES Teams(TeamID) ON DELETE CASCADE
);

-- =====================================================
-- VIEWS
-- =====================================================

-- View 1: Top Scoring Teams
-- Shows teams with their total goals scored across all matches
CREATE OR REPLACE VIEW TopScoringTeams AS
SELECT
    t.TeamID,
    t.Name AS TeamName,
    COALESCE(SUM(
        CASE
            WHEN m.Team1ID = t.TeamID THEN m.Team1Score
            WHEN m.Team2ID = t.TeamID THEN m.Team2Score
            ELSE 0
        END
    ), 0) AS TotalGoals
FROM Teams t
LEFT JOIN Matches m ON (m.Team1ID = t.TeamID OR m.Team2ID = t.TeamID)
GROUP BY t.TeamID, t.Name
ORDER BY TotalGoals DESC;

-- View 2: Upcoming Matches
-- Shows matches that are scheduled for today or in the future
CREATE OR REPLACE VIEW UpcomingMatches AS
SELECT
    m.MatchID,
    m.MatchDate,
    t1.Name AS Team1Name,
    t2.Name AS Team2Name,
    m.Team1Score,
    m.Team2Score,
    tr.Name AS TournamentName
FROM Matches m
LEFT JOIN Teams t1 ON m.Team1ID = t1.TeamID
LEFT JOIN Teams t2 ON m.Team2ID = t2.TeamID
LEFT JOIN Tournaments tr ON m.TournamentID = tr.TournamentID
WHERE m.MatchDate >= CURDATE()
ORDER BY m.MatchDate ASC;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Stored Procedure 1: AddTeam
-- Adds a new team and automatically creates a Results entry
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS AddTeam(
    IN p_Name VARCHAR(100),
    IN p_CoachName VARCHAR(100),
    IN p_TournamentID INT
)
BEGIN
    DECLARE newTeamID INT;

    INSERT INTO Teams (Name, CoachName, TournamentID)
    VALUES (p_Name, p_CoachName, p_TournamentID);

    SET newTeamID = LAST_INSERT_ID();

    -- Auto-create a Results row for the new team
    INSERT INTO Results (TournamentID, TeamID, Wins, Losses, Draws, Points)
    VALUES (p_TournamentID, newTeamID, 0, 0, 0, 0);

    SELECT newTeamID AS TeamID;
END //
DELIMITER ;

-- Stored Procedure 2: GetMatchResults
-- Gets detailed match results with team names and winner
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS GetMatchResults(
    IN p_TournamentID INT
)
BEGIN
    SELECT
        m.MatchID,
        m.MatchDate,
        t1.Name AS Team1Name,
        t2.Name AS Team2Name,
        m.Team1Score,
        m.Team2Score,
        CASE
            WHEN m.Team1Score > m.Team2Score THEN t1.Name
            WHEN m.Team2Score > m.Team1Score THEN t2.Name
            ELSE 'Draw'
        END AS Winner
    FROM Matches m
    LEFT JOIN Teams t1 ON m.Team1ID = t1.TeamID
    LEFT JOIN Teams t2 ON m.Team2ID = t2.TeamID
    WHERE m.TournamentID = p_TournamentID
    ORDER BY m.MatchDate DESC;
END //
DELIMITER ;

-- Stored Procedure 3: UpdateMatchScore
-- Updates the score for a specific match and updates Results table
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS UpdateMatchScore(
    IN p_MatchID INT,
    IN p_Team1Score INT,
    IN p_Team2Score INT
)
BEGIN
    DECLARE v_TournamentID INT;
    DECLARE v_Team1ID INT;
    DECLARE v_Team2ID INT;

    -- Get match details
    SELECT TournamentID, Team1ID, Team2ID
    INTO v_TournamentID, v_Team1ID, v_Team2ID
    FROM Matches WHERE MatchID = p_MatchID;

    -- Update the match score
    UPDATE Matches
    SET Team1Score = p_Team1Score, Team2Score = p_Team2Score
    WHERE MatchID = p_MatchID;

    -- Update Results for Team 1
    IF v_TournamentID IS NOT NULL THEN
        -- Team 1 results
        IF p_Team1Score > p_Team2Score THEN
            UPDATE Results SET Wins = Wins + 1, Points = Points + 3
            WHERE TeamID = v_Team1ID AND TournamentID = v_TournamentID;
            UPDATE Results SET Losses = Losses + 1
            WHERE TeamID = v_Team2ID AND TournamentID = v_TournamentID;
        ELSEIF p_Team2Score > p_Team1Score THEN
            UPDATE Results SET Wins = Wins + 1, Points = Points + 3
            WHERE TeamID = v_Team2ID AND TournamentID = v_TournamentID;
            UPDATE Results SET Losses = Losses + 1
            WHERE TeamID = v_Team1ID AND TournamentID = v_TournamentID;
        ELSE
            UPDATE Results SET Draws = Draws + 1, Points = Points + 1
            WHERE TeamID = v_Team1ID AND TournamentID = v_TournamentID;
            UPDATE Results SET Draws = Draws + 1, Points = Points + 1
            WHERE TeamID = v_Team2ID AND TournamentID = v_TournamentID;
        END IF;
    END IF;

    SELECT 'Match score updated successfully' AS Message;
END //
DELIMITER ;

-- =====================================================
-- QUERIES (Examples of INSERT, UPDATE, DELETE, JOIN, SUBQUERY)
-- =====================================================

-- INSERT Queries
-- INSERT INTO Users (Username, Password) VALUES ('admin', SHA2('admin123', 256));
-- INSERT INTO Tournaments (Name, StartDate, EndDate, Location) VALUES ('Premier League 2025', '2025-03-01', '2025-06-30', 'Lahore');
-- INSERT INTO Teams (Name, CoachName, TournamentID) VALUES ('Lahore Lions', 'Ali Ahmed', 1);
-- INSERT INTO Players (Name, Age, Position, TeamID) VALUES ('Ahmed Khan', 25, 'Forward', 1);
-- INSERT INTO Matches (TournamentID, Team1ID, Team2ID, Team1Score, Team2Score, MatchDate) VALUES (1, 1, 2, 3, 1, '2025-03-15');

-- UPDATE Query
-- UPDATE Players SET Age = 26, Position = 'Midfielder' WHERE PlayerID = 1;

-- DELETE Query
-- DELETE FROM Matches WHERE MatchID = 1;

-- INNER JOIN Query
-- SELECT p.Name AS PlayerName, p.Age, p.Position, t.Name AS TeamName
-- FROM Players p
-- INNER JOIN Teams t ON p.TeamID = t.TeamID;

-- LEFT JOIN Query
-- SELECT t.Name AS TeamName, tr.Name AS TournamentName
-- FROM Teams t
-- LEFT JOIN Tournaments tr ON t.TournamentID = tr.TournamentID;

-- SUBQUERY: Players older than the average age
-- SELECT Name, Age, Position FROM Players
-- WHERE Age > (SELECT AVG(Age) FROM Players WHERE Age IS NOT NULL);

-- SUBQUERY: Teams in tournaments with more than 2 teams
-- SELECT t.Name AS TeamName, tr.Name AS TournamentName
-- FROM Teams t
-- INNER JOIN Tournaments tr ON t.TournamentID = tr.TournamentID
-- WHERE tr.TournamentID IN (
--     SELECT TournamentID FROM Teams GROUP BY TournamentID HAVING COUNT(*) > 2
-- );

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Default admin user (password: admin123, hashed with SHA-256)
INSERT INTO Users (Username, Password) VALUES ('admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9');

-- Sample Tournaments
INSERT INTO Tournaments (Name, StartDate, EndDate, Location) VALUES
('Premier League 2025', '2025-03-01', '2025-06-30', 'Lahore'),
('Karachi Cup 2025', '2025-04-15', '2025-07-20', 'Karachi'),
('Islamabad Open 2025', '2025-05-01', '2025-08-15', 'Islamabad');

-- Sample Teams
INSERT INTO Teams (Name, CoachName, TournamentID) VALUES
('Lahore Lions', 'Ali Ahmed', 1),
('Karachi Kings', 'Saeed Malik', 1),
('Islamabad United', 'Waqar Hassan', 2),
('Peshawar Warriors', 'Kamran Shah', 2),
('Quetta Falcons', 'Nasir Javed', 3),
('Multan Tigers', 'Rashid Latif', 3);

-- Sample Players
INSERT INTO Players (Name, Age, Position, TeamID) VALUES
('Ahmed Khan', 25, 'Forward', 1),
('Bilal Raza', 28, 'Midfielder', 1),
('Faizan Ali', 22, 'Defender', 1),
('Hassan Shahid', 30, 'Goalkeeper', 1),
('Imran Butt', 24, 'Forward', 2),
('Junaid Siddiqui', 27, 'Midfielder', 2),
('Kashif Mahmood', 23, 'Defender', 2),
('Omar Akmal', 29, 'Forward', 3),
('Shahid Afridi', 26, 'Midfielder', 3),
('Tariq Aziz', 21, 'Defender', 3),
('Usman Ghani', 31, 'Goalkeeper', 4),
('Wahab Riaz', 25, 'Forward', 4),
('Yasir Shah', 28, 'Midfielder', 5),
('Zahid Mahmood', 24, 'Defender', 5),
('Asad Shafiq', 27, 'Forward', 6);

-- Sample Matches
INSERT INTO Matches (TournamentID, Team1ID, Team2ID, Team1Score, Team2Score, MatchDate) VALUES
(1, 1, 2, 3, 1, '2025-03-15'),
(1, 2, 1, 2, 2, '2025-04-01'),
(2, 3, 4, 1, 0, '2025-04-20'),
(2, 4, 3, 0, 3, '2025-05-05'),
(3, 5, 6, 2, 1, '2025-05-10'),
(3, 6, 5, 1, 1, '2025-06-01');

-- Sample Results
INSERT INTO Results (TournamentID, TeamID, Wins, Losses, Draws, Points) VALUES
(1, 1, 1, 0, 1, 4),
(1, 2, 0, 1, 1, 1),
(2, 3, 2, 0, 0, 6),
(2, 4, 0, 2, 0, 0),
(3, 5, 1, 0, 1, 4),
(3, 6, 0, 1, 1, 1);
