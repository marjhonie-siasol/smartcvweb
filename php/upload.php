<?php
require_once 'config.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Upload failed.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to upload a file.';
    echo json_encode($response);
    exit;
}

if (isset($_FILES['resumeFile'])) {
    $file = $_FILES['resumeFile'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['docx', 'pdf', 'txt'];
    
    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 5000000) {
                $newFileName = uniqid('resume_', true) . '.' . $fileExt;
                $fileDestination = '../uploads/' . $newFileName;
                
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    $_SESSION['last_uploaded_filename'] = htmlspecialchars($fileName);
                    
                    $response['status'] = 'success';
                    $response['message'] = 'File uploaded successfully!';
                    $response['fileName'] = htmlspecialchars($fileName);
                    $response['uniqueFileName'] = $newFileName;
                } else {
                    $response['message'] = 'Failed to move uploaded file.';
                }
            } else {
                $response['message'] = 'Your file is too large (Max 5MB).';
            }
        } else {
            $response['message'] = 'There was an error uploading your file.';
        }
    } else {
        $response['message'] = 'File type must be .docx, .pdf, .txt only.<br>Please try again';
    }
} else {
    $response['message'] = 'No file was sent.';
}

echo json_encode($response);
?>