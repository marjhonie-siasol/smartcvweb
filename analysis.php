<?php
require_once 'php/config.php';
require_once 'php/ai_handler.php';
$Parsedown = new Parsedown();

if (!isset($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}
$user_id = $_SESSION['user_id'];

$unique_filename = isset($_GET['filename']) ? basename($_GET['filename']) : null;
$initial_prompt = isset($_GET['prompt']) ? htmlspecialchars($_GET['prompt']) : 'Analyze this resume.';
$user_facing_filename = $_SESSION['last_uploaded_filename'] ?? 'your_resume.docx';

if (!$unique_filename) die("Error: No file specified for analysis.");

$analysisResult = null;
$analysis_id = null;
$error_message = '';
$chat_history = [];


$sql_check = "SELECT id, score, summary, breakdown_table, analysis_details, improvements, final_thoughts FROM analysis_history WHERE unique_filename = ? AND user_id = ?";
if ($stmt_check = $conn->prepare($sql_check)) {
    $stmt_check->bind_param("si", $unique_filename, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($row = $result_check->fetch_assoc()) {

        $analysis_id = $row['id'];
        $analysisResult = [
            "overallScore" => $row['score'],
            "summary" => $Parsedown->text($row['summary']),
            "breakdownTable" => json_decode($row['breakdown_table'], true) ?: [],
            "analysisCards" => json_decode($row['analysis_details'], true) ?: [],
            "topImprovements" => json_decode($row['improvements'], true) ?: [],
            "finalThoughts" => $Parsedown->text($row['final_thoughts'] ?? '')
        ];
    }
    $stmt_check->close();
}


if ($analysis_id === null) {
    $filepath = __DIR__ . '/uploads/' . $unique_filename;
    $resume_content = extractTextFromFile($filepath);

    if (empty(trim($resume_content)) || str_starts_with($resume_content, 'Error:')) {
        $error_message = "<strong>Analysis Failed: Could not read text from the document.</strong><br><br>This usually happens if the file is an image-based PDF (e.g., from Canva or a scanner) or a DOCX with complex formatting. Please try re-saving your resume as a standard, text-based PDF or DOCX file and upload it again.";
    } else {

        $rawAnalysis = get_resume_analysis($resume_content, $initial_prompt);

        if (isset($rawAnalysis['overallScore']) && $rawAnalysis['overallScore'] > 0) {
            $analysisResult = $rawAnalysis;

            $analysisResult['summary'] = $Parsedown->text($rawAnalysis['summary']);
            $analysisResult['finalThoughts'] = $Parsedown->text($rawAnalysis['finalThoughts']);


            $sql_insert = "INSERT INTO analysis_history (user_id, user_facing_filename, unique_filename, initial_prompt, score, summary, breakdown_table, analysis_details, improvements, final_thoughts) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt_insert = $conn->prepare($sql_insert)) {
                $json_breakdown = json_encode($rawAnalysis['breakdownTable']);
                $json_cards = json_encode($rawAnalysis['analysisCards']);
                $json_improvements = json_encode($rawAnalysis['topImprovements']);


                $stmt_insert->bind_param("isssisssss", $user_id, $user_facing_filename, $unique_filename, $initial_prompt, $rawAnalysis['overallScore'], $rawAnalysis['summary'], $json_breakdown, $json_cards, $json_improvements, $rawAnalysis['finalThoughts']);
                $stmt_insert->execute();
                $analysis_id = $stmt_insert->insert_id;
                $stmt_insert->close();
            }
        } else {
            $error_message = $rawAnalysis['topImprovements'][0] ?? 'An unknown error occurred during AI analysis.';
        }
    }
}


if ($analysis_id) {
    $sql_get_chat = "SELECT sender, message FROM chat_messages WHERE analysis_id = ? ORDER BY sent_at ASC";
    if ($stmt_chat = $conn->prepare($sql_get_chat)) {
        $stmt_chat->bind_param("i", $analysis_id);
        $stmt_chat->execute();
        $result_chat = $stmt_chat->get_result();
        while ($row_chat = $result_chat->fetch_assoc()) {

            $row_chat['message'] = $row_chat['sender'] === 'ai' ? $Parsedown->text($row_chat['message']) : '<p>' . nl2br(htmlspecialchars($row_chat['message'])) . '</p>';
            $chat_history[] = $row_chat;
        }
        $stmt_chat->close();
    }
}


$history_items = [];
$sql_history = "SELECT unique_filename, user_facing_filename, initial_prompt FROM analysis_history WHERE user_id = ? ORDER BY analyzed_at DESC LIMIT 10";
if ($stmt_history = $conn->prepare($sql_history)) {
    $stmt_history->bind_param("i", $user_id);
    $stmt_history->execute();
    $result_history = $stmt_history->get_result();
    while ($row = $result_history->fetch_assoc()) {
        $history_items[] = $row;
    }
    $stmt_history->close();
}


$overallScore = $analysisResult['overallScore'] ?? 0;
$breakdownTable = $analysisResult['breakdownTable'] ?? [];
$analysisCards = $analysisResult['analysisCards'] ?? [];
$topImprovements = $analysisResult['topImprovements'] ?? [];
$summary = $analysisResult['summary'] ?? 'Analysis could not be generated.';
$finalThoughts = $analysisResult['finalThoughts'] ?? 'No final summary available.';
$impact_score_100 = $analysisCards['impactScore']['score'] ?? 0;
$clarity_score_100 = $analysisCards['clarityScore']['score'] ?? 0;
$ats_score_100 = $analysisCards['atsScore']['score'] ?? 0;
$impact_feedback = $analysisCards['impactScore']['feedback'] ?? 'N/A';
$clarity_feedback = $analysisCards['clarityScore']['feedback'] ?? 'N/A';
$ats_feedback = $analysisCards['atsScore']['feedback'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Analysis - Smart CV</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>

<body>
    <div id="app-container">
        <nav id="sidebar">
            <div id="sidebar-close-trigger" class="sidebar-header logo-icon-wrapper">
                <img src="images/smartCV-logo.png" alt="Close Menu" class="logo">
            </div>
            <div class="sidebar-top">
                <a href="index.php" class="sidebar-link new-analyze-link"><img src="images/new-analyze-white.png" class="icon-dark"><img src="images/new-analyze-dark.png" class="icon-light"><span>New analyze</span></a>
                <div class="history-section">
                    <h3 class="history-title">History</h3>
                    <div id="history-list">
                        <?php foreach ($history_items as $item):
                            $analysis_url = "analysis.php?prompt=" . urlencode($item['initial_prompt']) . "&filename=" . urlencode($item['unique_filename']);
                        ?>
                            <a href="<?php echo $analysis_url; ?>" class="history-item <?php echo $item['unique_filename'] == $unique_filename ? 'active' : ''; ?>"><?php echo htmlspecialchars($item['user_facing_filename']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="sidebar-bottom"><a href="php/logout.php" class="sidebar-link"><img src="images/signout-white.png" class="icon-dark"><img src="images/signout-dark.png" class="icon-light"><span>Sign out</span></a></div>
        </nav>

        <div id="main-content" class="analysis-page-content">
            <button id="sidebar-open-trigger" title="Open Menu">
                <img src="images/favicon.png" alt="Open Menu">
            </button>

            <header class="analysis-header">
                <div class="page-title-container">
                    <h1>Your Resume Check Score</h1>
                    <button id="guide-icon" title="Scoring Guide">
                        <img src="images/guide-dark.png" class="icon-light" alt="Guide">
                        <img src="images/guide-light.png" class="icon-dark" alt="Guide">
                    </button>
                </div>
                <div class="header-actions">
                    <a href="index.php" class="analyze-btn-header">Analyze another resume</a>
                    <button id="theme-toggle-btn-header" class="theme-toggle" title="Toggle Theme">
                        <img src="images/white-mode.png" alt="Light mode" class="icon-dark">
                        <img src="images/dark-mode.png" alt="Dark mode" class="icon-light">
                    </button>
                </div>
            </header>

            <p class="file-name header-file-name"><?php echo htmlspecialchars($user_facing_filename); ?></p>

            <?php if (!empty($error_message)): ?>
                <div class="error-box">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php else: ?>
                <div class="analysis-layout">
                    <div class="scores-column">
                        <div class="total-score-card-new">
                            <span class="score-card-title">Total Score</span>
                            <div id="overall-score-display" class="total-score-display" data-score="<?php echo $overallScore; ?>">0<span>/100</span></div>
                        </div>

                        <div class="score-breakdown-card">
                            <table class="score-breakdown-table">
                                <?php
                                $max_scores = [
                                    'Clarity & Conciseness' => 20,
                                    'Impact & Results' => 30,
                                    'Skills & Keywords' => 25,
                                    'Formatting & ATS' => 25,
                                ];

                                foreach ($breakdownTable as $item):
                                    $clean_category = trim(preg_replace('/\(max \d+\)/i', '', $item['category']));
                                    $max_score = $max_scores[$clean_category] ?? 0;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($clean_category); ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['score']); ?></strong> / <?php echo $max_score; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>

                        <div class="metric-cards-container">
                            <div class="metric-card">
                                <h3><span class="card-icon">🎯</span> Impact & Quantification</h3>
                                <div id="impact-score-display" class="metric-score" data-score="<?php echo $impact_score_100; ?>"><span>0<small>/100</small></span></div>
                                <p><?php echo htmlspecialchars($impact_feedback); ?></p>
                            </div>
                            <div class="metric-card">
                                <h3><span class="card-icon">✍️</span> Clarity & Brevity</h3>
                                <div id="clarity-score-display" class="metric-score" data-score="<?php echo $clarity_score_100; ?>"><span>0<small>/100</small></span></div>
                                <p><?php echo htmlspecialchars($clarity_feedback); ?></p>
                            </div>
                            <div class="metric-card">
                                <h3><span class="card-icon">🤖</span> ATS Compatibility</h3>
                                <div id="ats-score-display" class="metric-score" data-score="<?php echo $ats_score_100; ?>"><span>0<small>/100</small></span></div>
                                <p><?php echo htmlspecialchars($ats_feedback); ?></p>
                            </div>
                        </div>
                        <div class="improvements-card">
                            <h3>Top Improvements</h3>
                            <ul class="improvements-list">
                                <?php if (empty($topImprovements)): ?>
                                    <li><span class="icon-check"></span> Great work! No major improvements suggested.</li>
                                <?php else: ?>
                                    <?php foreach ($topImprovements as $item): ?>
                                        <li><span class="icon-improvement"></span>
                                            <div><?php echo htmlspecialchars($item); ?></div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="final-summary-card">
                            <h3>Final Summary</h3>
                            <div class="final-summary-content"><?php echo $finalThoughts; ?></div>
                        </div>
                    </div>

                    <div class="summary-column">
                        <section class="chat-interface">
                            <div class="chat-log" id="chat-log"></div>
                            <div class="prompt-section chat-prompt-section">
                                <div class="prompt-bar">
                                    <textarea id="chat-prompt-input" placeholder="Ask for more details..." rows="1"></textarea>
                                    <input type="hidden" id="chat-analysis-id" value="<?php echo $analysis_id; ?>">
                                    <button id="chat-send-btn">Send</button>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="guide-modal" class="modal-overlay">
        <div class="modal-content">
            <div id="guide-modal-close" class="modal-close-btn"></div>
            <h2>Understanding Your Score</h2>
            <div class="modal-section">
                <h3>Total Score (0-100)</h3>
                <p>This is your overall resume grade, combining all factors. Higher scores indicate a strong, ATS-friendly resume with clear, impactful content.</p>
                <ul>
                    <li><strong class="score-color-great">90-100:</strong> Excellent. Ready to apply.</li>
                    <li><strong class="score-color-good">75-89:</strong> Good. Minor tweaks will make it great.</li>
                    <li><strong class="score-color-average">60-74:</strong> Average. Needs improvement in key areas.</li>
                    <li><strong class="score-color-poor">Below 60:</strong> Needs significant work.</li>
                </ul>
            </div>
            <div class="modal-section">
                <h3>Score Breakdown</h3>
                <p>This shows how your total score is calculated from four key categories:</p>
                <ul>
                    <li><strong>Impact & Quantification (30 pts):</strong> Measures your use of numbers, metrics, and strong action verbs to show your achievements, not just your duties.</li>
                    <li><strong>Clarity & Conciseness (20 pts):</strong> Assesses readability, grammar, and whether your bullet points are brief yet powerful.</li>
                    <li><strong>Skills & Keywords (25 pts):</strong> Checks for relevant skills and keywords that Applicant Tracking Systems (ATS) and recruiters look for.</li>
                    <li><strong>Formatting & ATS (25 pts):</strong> Evaluates your resume's structure, fonts, and use of standard sections for compatibility with automated screening software.</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        const initialChat = [{
                sender: 'user',
                message: `<?php echo '<p>' . nl2br(addslashes(htmlspecialchars($initial_prompt))) . '</p>'; ?>`
            },
            {
                sender: 'ai',

                message: `<?php echo addslashes($summary); ?>`
            },
            <?php foreach ($chat_history as $message): ?> {
                    sender: '<?php echo $message['sender']; ?>',
                    message: `<?php echo addslashes($message['message']); ?>`
                },
            <?php endforeach; ?>
        ];
    </script>
    <script src="js/script.js"></script>
    <script src="js/analysis.js"></script>
</body>

</html>