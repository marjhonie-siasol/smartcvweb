<?php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($fullname) || empty($email) || empty($password)) {
        $response['message'] = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
    } elseif (strlen($password) < 6) {
        $response['message'] = 'Password must have at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $response['message'] = 'Passwords do not match.';
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $response['message'] = 'This email is already taken.';
            } else {
                $sql_insert = "INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)";
                if ($stmt_insert = $conn->prepare($sql_insert)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_insert->bind_param("sss", $fullname, $email, $hashed_password);
                    if ($stmt_insert->execute()) {
                        $response['status'] = 'success';
                        $response['message'] = 'Signup successful! You can now log in.';
                    } else {
                        $response['message'] = 'Oops! Something went wrong. Please try again later.';
                    }
                    $stmt_insert->close();
                }
            }
            $stmt->close();
        }
    }
    $conn->close();
}

echo json_encode($response);
