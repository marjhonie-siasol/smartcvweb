<?php

use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Cell;

function extractTextFromElement($element)
{
    $text = '';
    if ($element instanceof TextRun || $element instanceof Text) {
        $text .= $element->getText() . ' ';
    } elseif ($element instanceof Table) {
        foreach ($element->getRows() as $row) {
            foreach ($row->getCells() as $cell) {
                $text .= extractTextFromElement($cell) . "\t";
            }
            $text .= "\n";
        }
    } elseif ($element instanceof Row || $element instanceof Cell || method_exists($element, 'getElements')) {
        foreach ($element->getElements() as $innerElement) {
            $text .= extractTextFromElement($innerElement);
        }
    }
    return $text;
}


function extractTextFromFile(string $filepath): string
{

    if (!file_exists($filepath)) {
        return "Error: File not found at path: " . htmlspecialchars($filepath);
    }

    $file_extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

    if ($file_extension === 'txt') {
        return file_get_contents($filepath);
    }
    if ($file_extension === 'pdf') {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filepath);
            return $pdf->getText();
        } catch (Exception $e) {
            return "Error parsing PDF file: " . $e->getMessage();
        }
    }
    if ($file_extension === 'docx') {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($filepath);
            $fullText = '';
            foreach ($phpWord->getSections() as $section) {
                $fullText .= extractTextFromElement($section);
            }
            return $fullText;
        } catch (Exception $e) {
            return "Error parsing DOCX file: " . $e->getMessage();
        }
    }

    return "Unsupported file type: {$file_extension}. This application can currently only process .txt, .pdf, and .docx files.";
}

function get_resume_analysis($resume_content, $user_prompt)
{
    $apiKey = GOOGLE_AI_API_KEY;
    $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

    $full_api_prompt = "
    As an expert AI Resume Analyzer, your task is to perform a comprehensive review of the provided resume. Your analysis must be based on modern, industry-standard hiring practices and ATS (Applicant Tracking System) compatibility.

    Resume Content: \"{$resume_content}\"
    User Request: \"{$user_prompt}\"

    You must respond with ONLY a single, valid JSON object. Do not include markdown or any other text outside the JSON structure.

    The JSON object must have this exact structure:
    {
      \"overallScore\": An integer from 0 to 100, which is the sum of the 'score' values from the 'breakdownTable' below.
      \"breakdownTable\": An array of objects, each with a \"category\" (string) and a \"score\" (integer). Use these exact categories and their max possible scores: 'Clarity & Conciseness (max 20)', 'Impact & Results (max 30)', 'Skills & Keywords (max 25)', 'Formatting & ATS (max 25)'. The sum of scores must equal 'overallScore'.
      \"analysisCards\": {
        \"impactScore\": { \"score\": An integer 0-100, \"feedback\": \"A VERY short phrase about their use of metrics. Max 8 words.\" },
        \"clarityScore\": { \"score\": An integer 0-100, \"feedback\": \"A VERY short phrase about conciseness and readability. Max 8 words.\" },
        \"atsScore\": { \"score\": An integer 0-100, \"feedback\": \"A VERY short phrase about ATS-friendliness. Max 8 words.\" }
      },
      \"topImprovements\": An array of 2-3 short, highly actionable strings for what to improve first.
      \"summary\": \"A conversational opening response for the chat window that directly addresses the user's request: '{$user_prompt}'. Your response should be friendly and start with an acknowledgment, like 'Of course!' or 'Absolutely!'. Then, briefly state that you have analyzed the resume and are presenting the key findings. This should be 2-3 sentences and start with a relevant emoji. Example: '🚀 Absolutely! I've analyzed your resume with a focus on providing honest, actionable feedback. Here is a summary of the analysis:'\",
      \"finalThoughts\": \"A final, encouraging summary report of 2-3 sentences, formatted as a single paragraph. This should summarize the overall state of the resume and the path forward. It will be displayed separately from the chat.\"
    }

    **CRITICAL SCORING RULE:**
    The 'score' in the 'analysisCards' MUST be a direct normalization of the scores from the 'breakdownTable'.
    - 'analysisCards.impactScore.score' = ('breakdownTable.Impact & Results.score' / 30) * 100, rounded to the nearest integer.
    - 'analysisCards.clarityScore.score' = ('breakdownTable.Clarity & Conciseness.score' / 20) * 100, rounded to the nearest integer.
    - 'analysisCards.atsScore.score' = (('breakdownTable.Skills & Keywords.score' + 'breakdownTable.Formatting & ATS.score') / (25 + 25)) * 100, rounded to the nearest integer.
    This ensures the visual scores are consistent with the detailed breakdown.
    ";

    $data = ['contents' => [['parts' => [['text' => $full_api_prompt]]]], 'safetySettings' => [['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'], ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'], ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'], ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE']]];
    $json_data = json_encode($data);

    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $api_url, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $json_data, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45, CURLOPT_SSL_VERIFYPEER => false]);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($result === false || $http_code != 200) {
        return ["topImprovements" => ["Error: Could not connect to the AI analysis service."], "summary" => ""];
    }

    $response_data = json_decode($result, true);
    $ai_text_response = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $json_string = trim(str_replace(['```json', '```'], '', $ai_text_response));

    if ($json_string) {
        $final_data = json_decode($json_string, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($final_data['overallScore'])) {
            return $final_data;
        }
    }
    return ["topImprovements" => ["Error: The AI returned a response that could not be understood."], "summary" => ""];
}
