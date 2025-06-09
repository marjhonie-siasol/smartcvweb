<?php
require_once 'php/config.php';
$is_logged_in = isset($_SESSION['user_id']);
$history_items = [];

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $sql_history = "SELECT id, user_facing_filename, unique_filename, initial_prompt FROM analysis_history WHERE user_id = ? ORDER BY analyzed_at DESC LIMIT 10";
    if ($stmt_history = $conn->prepare($sql_history)) {
        $stmt_history->bind_param("i", $user_id);
        $stmt_history->execute();
        $result = $stmt_history->get_result();
        while ($row = $result->fetch_assoc()) {
            $history_items[] = $row;
        }
        $stmt_history->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart CV - AI Resume Analyzer</title>
    <link rel="icon" href="images/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>

<body>
    <div id="app-container" class="sidebar-collapsed" data-logged-in="<?php echo $is_logged_in ? 'true' : 'false'; ?>">

        <nav id="sidebar">

            <div class="sidebar-header">
                <div class="logo-icon-wrapper" id="sidebar-close-trigger">
                    <img src="images/smartCV-logo.png" alt="Smart CV Logo" class="logo">
                </div>
            </div>
            <div class="sidebar-top">
                <a href="index.php" class="sidebar-link new-analyze-link">
                    <img src="images/new-analyze-white.png" alt="New Analyze Icon" class="icon-dark">
                    <img src="images/new-analyze-dark.png" alt="New Analyze Icon" class="icon-light">
                    <span>New analyze</span>
                </a>
                <div class="history-section">
                    <h3 class="history-title">History</h3>
                    <div id="history-list">
                        <?php foreach ($history_items as $item):
                            $analysis_url = "analysis.php?prompt=" . urlencode($item['initial_prompt']) . "&filename=" . urlencode($item['unique_filename']);
                        ?>
                            <a href="<?php echo $analysis_url; ?>" class="history-item">
                                <?php echo htmlspecialchars($item['user_facing_filename']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="sidebar-bottom">
                <?php if ($is_logged_in): ?>
                    <a href="php/logout.php" class="sidebar-link">
                        <img src="images/signout-white.png" alt="Sign Out Icon" class="icon-dark">
                        <img src="images/signout-dark.png" alt="Sign Out Icon" class="icon-light">
                        <span>sign out</span>
                    </a>
                <?php else: ?>
                    <a href="#" class="sidebar-link" id="sidebar-login-btn">
                        <img src="images/login-account-white.png" alt="Login Icon" class="icon-dark">
                        <img src="images/login-account-dark.png" alt="Login Icon" class="icon-light">
                        <span>Log in your account</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <div id="main-content">
            <button id="sidebar-open-trigger" title="Open Menu">
                <img src="images/favicon.png" alt="Open Menu">
            </button>
            <header>

                <div class="auth-buttons">
                    <?php if ($is_logged_in): ?>
                        <a href="php/logout.php" class="sign-out-btn">
                            <span>sign out</span>
                            <img src="images/signout-white.png" alt="Sign Out Icon" class="sign-out-icon icon-dark">
                            <img src="images/signout-dark.png" alt="Sign Out Icon" class="sign-out-icon icon-light">
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary" id="header-login-btn">Log in</button>
                        <button class="btn btn-primary" id="header-signup-btn">Sign up</button>
                    <?php endif; ?>
                </div>
            </header>

            <main>
                <div class="upload-section">
                    <p class="upload-title">drag or upload your resume here and let Smart CV help you improve it!</p>
                    <div class="upload-box" id="upload-box">
                        <input type="file" id="resume-upload-input" accept=".docx,.pdf,.txt" hidden>
                        <img src="images/upload-resume.png" alt="Upload Icon" class="upload-icon">
                    </div>
                    <p id="file-name-display"></p>
                </div>

                <form class="prompt-section" id="analyze-form" action="analysis.php" method="GET">
                    <div class="prompt-bar">
                        <textarea id="prompt-input" name="prompt" placeholder="Ask me anything about your resume" rows="1" required></textarea>
                        <input type="hidden" name="filename" id="hidden-filename">
                        <button type="submit" id="analyze-btn" class="disabled">Analyze</button>
                    </div>
                </form>
            </main>
        </div>
        <div id="main-overlay"></div>
    </div>

    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">×</span>
            <h2>Log in</h2>
            <form id="login-form">
                <p class="form-error" id="login-error"></p>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn btn-login">Login</button>
            </form>
            <p class="switch-form">No account yet? <a href="#" id="go-to-signup">Sign up here.</a></p>
        </div>
    </div>
    <div id="signup-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">×</span>
            <h2>Sign up</h2>
            <form id="signup-form">
                <p class="form-error" id="signup-error"></p>
                <input type="text" name="fullname" placeholder="Fullname" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit" class="btn btn-signup">Signup</button>
            </form>
            <p class="switch-form">Already have an account? <a href="#" id="go-to-login">Log in here.</a></p>
        </div>
    </div>
    <div id="message-modal" class="modal">
        <div class="modal-content message-box">
            <p id="message-text"></p>
            <button id="message-ok-btn" class="btn">Okay</button>
        </div>
    </div>

    <button id="theme-toggle-btn" class="theme-toggle" title="Toggle Theme">
        <img src="images/white-mode.png" alt="Light mode" class="icon-dark">
        <img src="images/dark-mode.png" alt="Dark mode" class="icon-light">
    </button>

    <script src="js/script.js"></script>
</body>

</html>