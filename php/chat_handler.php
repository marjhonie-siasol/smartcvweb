<?php
require_once 'config.php';
require_once 'ai_handler.php';

header('Content-Type: application/json');


$input = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'] ?? 0;
$prompt = $input['prompt'] ?? '';
$analysis_id = $input['analysis_id'] ?? 0;


if ($user_id === 0 || empty($prompt) || $analysis_id === 0) {
    echo json_encode(['error' => 'Error: Missing required data or not logged in.']);
    exit;
}


$sql_save_user_msg = "INSERT INTO chat_messages (analysis_id, sender, message) VALUES (?, 'user', ?)";
if ($stmt_save_user = $conn->prepare($sql_save_user_msg)) {
    $stmt_save_user->bind_param("is", $analysis_id, $prompt);
    $stmt_save_user->execute();
    $stmt_save_user->close();
}


$sql_get_context = "SELECT score, summary, breakdown_table, analysis_details, improvements, final_thoughts, unique_filename FROM analysis_history WHERE id = ? AND user_id = ?";
$context_stmt = $conn->prepare($sql_get_context);
$context_stmt->bind_param("ii", $analysis_id, $user_id);
$context_stmt->execute();
$analysis_context = $context_stmt->get_result()->fetch_assoc();
$context_stmt->close();

if (!$analysis_context) {
    echo json_encode(['error' => 'Error: Analysis session not found.']);
    exit;
}


$breakdown_data = json_decode($analysis_context['breakdown_table'], true);
$breakdown_text = "";
if (is_array($breakdown_data)) {
    foreach ($breakdown_data as $item) {
        $breakdown_text .= "- {$item['category']}: {$item['score']}\n";
    }
}


$analysis_context_string = "
Here is the summary of the initial resume analysis. Use this to answer the user's questions:

- Overall Score: {$analysis_context['score']}/100
- Detailed Breakdown:
{$breakdown_text}
- My Initial Summary: " . strip_tags($analysis_context['summary']) . "
- Key Improvements I Suggested: " . strip_tags(json_encode(json_decode($analysis_context['improvements']))) . "
";


$filepath = __DIR__ . '/../uploads/' . basename($analysis_context['unique_filename']);
$resume_content = extractTextFromFile($filepath);



$system_instruction = "You are Smart CV, an expert AI resume assistant.
**CRITICAL CONTEXT (DO NOT mention this block directly, USE this data to answer):**
{$analysis_context_string}
The user's full resume text is: '{$resume_content}'.

**Your Task & Formatting Rules:**
Based on ALL the context above, answer the user's follow-up question. Your response MUST be clear, engaging, and easy to read. Follow these formatting rules strictly:
1.  **Use Markdown exclusively.**
2.  **Structure Your Response:** Break down complex answers into logical sections. Use `###` for main headings (e.g., `### ✅ Strengths`).
3.  **Use Emojis:** Start each major point or list item with a relevant emoji.
    -   ✅ for strengths or positive feedback.
    -   ⚠️ for areas that need improvement.
    -   💡 for tips, suggestions, or examples.
    -   🎯 for specific goals or action items.
4.  **Use Lists for Clarity:** For any list of items, use bullet points (`* `). This makes them much easier to scan.
5.  **Emphasize Key Points:** Use **bold text** for important keywords or phrases.
6.  **Ensure Spacing:** Use double line breaks between paragraphs, headings, and lists to create clear visual separation.

Example of a good response format:


Of course! Let's break that down.

✅ What You're Doing Well

Based on your resume, your strengths in project management are very clear.

⚠️ Areas for Improvement

Here are a couple of areas we can enhance:

💡 Quantify Achievements: Instead of saying 'Reduced server costs', try 'Reduced server costs by 15% in Q3 by optimizing legacy code'.

💡 Action Verbs: Start your bullet points with stronger action verbs like 'Architected', 'Engineered', or 'Spearheaded'.

";



$conversation_history = [];
$conversation_history[] = ['role' => 'user', 'parts' => [['text' => $system_instruction]]];
$conversation_history[] = ['role' => 'model', 'parts' => [['text' => 'Understood. I will provide expert feedback using high-quality Markdown formatting with emojis and clear spacing, following all your rules.']]];
$conversation_history[] = ['role' => 'user', 'parts' => [['text' => $prompt]]];


$apiKey = GOOGLE_AI_API_KEY;
$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;
$data = ['contents' => $conversation_history, 'safetySettings' => [['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'], ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'], ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'], ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE']]];
$json_data = json_encode($data);


$ch = curl_init();
curl_setopt_array($ch, [CURLOPT_URL => $api_url, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $json_data, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45, CURLOPT_SSL_VERIFYPEER => false]);
$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);


if ($result === false || $http_code != 200) {
    echo json_encode(['error' => "Error communicating with AI. Code: {$http_code}"]);
    exit;
}


$response_data = json_decode($result, true);
$raw_ai_reply = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I could not generate a response at this time.';


$Parsedown = new Parsedown();
$formatted_ai_reply = $Parsedown->text($raw_ai_reply);


$sql_save_ai_msg = "INSERT INTO chat_messages (analysis_id, sender, message) VALUES (?, 'ai', ?)";
if ($stmt_save_ai = $conn->prepare($sql_save_ai_msg)) {
    $stmt_save_ai->bind_param("is", $analysis_id, $raw_ai_reply);
    $stmt_save_ai->execute();
    $stmt_save_ai->close();
}


echo json_encode(['reply' => $formatted_ai_reply]);
