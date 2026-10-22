<?php
include "db-config.php";

session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    if (isset($_POST['action']))
    {
        $action = $_POST['action'];

        if ($action == "login")
        {
            $email =  isset($_POST['email']) ? $_POST['email'] : '';
            $password =  isset($_POST['password']) ? $_POST['password'] : '';
            if (LoginUser($email, $password))
            {
                echo json_encode(["success" => true]);
            }
            else 
            {
                echo json_encode(['success' => false]);
            }
            exit;
        }
        else if ($action == "logout")
        {
            $_SESSION['userID'] = -1;
            unset($_SESSION['username']);
            unset($_SESSION['userType']);
            setcookie('project1', $_COOKIE['project1'], time() - (365*24*60*60));
            setcookie('project2', $_COOKIE['project2'], time() - (365*24*60*60));
            unset($_COOKIE['project1']);
            unset($_COOKIE['project2']);
            unset($_COOKIE['topic1']);
            unset($_COOKIE['topic2']);
            setcookie('topic1', $_COOKIE['topic1'], time() - (365*24*60*60));
            setcookie('topic2', $_COOKIE['topic2'], time() - (365*24*60*60));
            session_destroy();

            echo json_encode(['success' => true]);
        }
    }
}

function LoginUser($email, $password) : array|bool   
{ 
    global $conn;

    if (empty($email) || empty($password)) {
        return False;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return False;
    }
    else if (!str_ends_with($email, "@make-it-all.co.uk")) {
        return False;
    }


    $sql = "SELECT * FROM Users WHERE Email = ? AND (UserType != 'Deleted' AND UserType != 'Inactive')";
    $result = mysqli_execute_query($conn, $sql, [$email]);

    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['Password']) === false) {
            return False;
        }

        $_SESSION['userID'] = $user['UserID'];
        $_SESSION['username'] = $user['Forename'] .' '. $user['Surname'];
        $_SESSION['userType'] = $user['UserType'];
    
        return $user;
    }
    
    return False;
}