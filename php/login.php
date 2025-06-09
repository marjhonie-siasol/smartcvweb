<?php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $response['message'] = 'Please enter both email and password.';
    } else {
        $sql = "SELECT id, fullname, password FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $fullname, $hashed_password);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {

                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["fullname"] = $fullname;

                            $response['status'] = 'success';
                            $response['message'] = 'Login successful!';
                        } else {
                            $response['message'] = 'The password you entered was not valid.';
                        }
                    }
                } else {
                    $response['message'] = 'No account found with that email.';
                }
            } else {
                $response['message'] = 'Oops! Something went wrong. Please try again later.';
            }
            $stmt->close();
        }
    }
    $conn->close();
}

echo json_encode($response);
