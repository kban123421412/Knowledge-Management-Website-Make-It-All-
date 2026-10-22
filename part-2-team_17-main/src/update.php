<?php

session_start();
//if (!isset($_SESSION['userID'])) { exit("Access Denied"); }

include "db-config.php";
include "function.php";

function CleanData($data): string 
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);

    //$data = mysqli_real_escape_string($conn, $data); // Not needed when using prepared statements
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    if (isset($_POST['action']))
    {
        $action = $_POST['action'];


        if ($action == "task")
        {
            $taskID =  isset($_POST['taskID']) ? $_POST['taskID'] : '';
            $newStatus = isset($_POST['status']) ? $_POST['status'] : '';

            if (UpdateTask($taskID, null, null, null, $newStatus, null))
            {
                echo json_encode(["success" => true]);
            }
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to update task status."]);
            }
            exit;
        }
        else if ($action == "project")
        {
            $projectID = isset($_POST['projectID']) ? $_POST['projectID'] : '';

            $projectTitle = isset($_POST['title']) ? $_POST['title'] : '';
            $projectDeadline = isset($_POST['deadline']) ? $_POST['deadline'] : '';
            $projectDescription = isset($_POST['description']) ? $_POST['description'] : '';

            if (UpdateProject($projectID, $projectTitle, $projectDescription, $projectDeadline))
            {
                DeleteProjectTags($projectID);
                DeleteProjectMembers($projectID);

                $tags = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : [];
                
                foreach ($tags as $tag)
                {
                    if (is_numeric($tag))
                    {
                        AddProjectTag($projectID,intval($tag));
                    }
                }

                $teamLeaders = isset($_POST['team-leaders']) && is_array($_POST['team-leaders']) ? $_POST['team-leaders'] : [];

                foreach ($teamLeaders as $teamLeader)
                {
                    if (is_numeric($teamLeader))
                    {
                        AddProjectMember($projectID, $teamLeader, true);
                    }
                }

                $users = isset($_POST['users']) && is_array($_POST['users']) ? $_POST['users'] : [];

                foreach ($users as $user)
                {
                    if (is_numeric($user))
                    {
                        AddProjectMember($projectID, $user, false);
                    }
                }

                echo json_encode(["success" => true]);
            }
        }
        else if ($action == "topic")
        {
            $topicID = isset($_POST['topicID']) ? $_POST['topicID'] : '';
            $topicTitle = isset($_POST['title']) ? $_POST['title'] : '';

            if (UpdateTopic($topicID, $topicTitle))
            {
                DeleteTopicTags($topicID);
                DeleteTopicMembers($topicID);

                $tags = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : [];
                
                foreach ($tags as $tag)
                {
                    if (is_numeric($tag))
                    {
                        AddTopicTag($topicID,intval($tag));
                    }
                }

                $users = isset($_POST['users']) && is_array($_POST['users']) ? $_POST['users'] : [];

                foreach ($users as $user)
                {
                    if (is_numeric($user))
                    {
                        AddTopicMember($topicID, $user);
                    }
                }

                echo json_encode(["success" => true]);
            }
        }
        else if ($action == "user")
        {
            $userID = isset($_POST['userID']) ? $_POST['userID'] : '';
            $userType = isset($_POST['userType']) ?  $_POST['userType'] : '';

            if (UpdateUser($userID, null, null, null, null, $userType))
            {
                echo json_encode(["success" => true]);
            }
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to activate user."]);
            }
            exit;
            
        }
        else if ($action == "update-user")
        {
            $userID = isset($_POST['userID']) ? $_POST['userID'] : '';
            $forename = isset($_POST['forename']) ? $_POST['forename'] : '';
            $surname = isset($_POST['surname']) ? $_POST['surname'] : '';
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if (UpdateUser($userID, $email, $password, $forename, $surname, null))
            {
                $_SESSION['username'] = $forename .' '.$surname;
                echo json_encode(["success" => true]);
            }
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to update user."]);
            }
            exit;
            
        }

    }
}

function UpdateUser($userID, $email = null, $password = null, $forename = null, $surname = null, $userType = null) : bool
{
    global $conn;
    //echo "$userID, $email, $password, $forename, $surname, $userType";

    $userID = is_numeric($userID) ? intval($userID) : null;
    if($userID === null ) {
        //error_log("Invalid UserID: " . $userID);
        return False;

    }

    $email = CleanData($email);
    $forename = CleanData($forename);
    $surname = CleanData($surname);
    $userType = CleanData($userType);


    $params = [];
    $newValues = [];

    if (!empty($email)) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && str_ends_with($email, "@make-it-all.co.uk")) {
            $newValues[] = "Email = ?";
            $params[] = $email;
        }
        else {
            //error_log("Invalid Email: " . $email); 
            return False;
        }
    }

    if (!empty($password)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $newValues[] = "Password = ?";
        $params[] = $password;
    }

    if (!empty($forename)) {
        $newValues[] = "Forename = ?";
        $params[] = $forename;
    }

    if (!empty($surname)) {
        $newValues[] = "Surname = ?";
        $params[] = $surname;
    }

    if (!empty($userType)) {
        $newValues[] = "UserType = ?";
        $params[] = $userType;
    }

    if (empty($newValues)) {
        error_log("No fields to update for UserID: " . $userID);
        return False; // No fields to update
    }

    //echo "$params";
    //echo "$newValues";

    $params[] = $userID; 

    $sql = "UPDATE Users SET " . implode(", ", $newValues) . " WHERE UserID = ?";

    $result = mysqli_execute_query($conn, $sql, $params);

    return ($result);
}


function UpdateProject($projectID, $title = null, $description = null, $deadline = null) : bool
{
    global $conn;

    $projectID = is_numeric($projectID) ? intval($projectID) : null;
    if($projectID === null ) {
        return False;
    }

    $title = CleanData($title);
    $description = CleanData($description);
    $deadline = CleanData($deadline);


    $params = [];
    $newValues = [];

    if (!empty($title)) {
        $newValues[] = "Title = ?";
        $params[] = $title;
    }

    if (!empty($description)) {
        $newValues[] = "Description = ?";
        $params[] = $description;
    }

    if (!empty($deadline)) {
        $newValues[] = "Deadline = ?";
        $params[] = $deadline;
    }

    if (empty($newValues)) {
        error_log("No fields to update for ProjectID: " . $projectID);
        return False; // No fields to update
    }

    $params[] = $projectID;
    $sql = "UPDATE Projects SET " . implode(", ", $newValues) . " WHERE ProjectID = ?";
    $result = mysqli_execute_query($conn, $sql, $params);

    return ($result);

}

function DeleteProjectTags($projectID) 
{
    global $conn;

    $projectID = is_numeric($projectID) ? intval($projectID) : null;

    if ($projectID === null) {
        return False;
    }

    $sql = "DELETE FROM ProjectTags WHERE ProjectID = ?";
    $result = mysqli_execute_query($conn, $sql, [$projectID]);

    return ($result);

}

function DeleteProjectMembers($projectID)
{
    global $conn ;

    $projectID = is_numeric($projectID) ? intval($projectID) : null;
    
    if($projectID === null) {
        return False;
    }
    
    $sql = "DELETE FROM ProjectMembers WHERE ProjectID = ?";
    $result = mysqli_execute_query($conn, $sql, [$projectID]);

    return ($result);
}

function DeleteTopicTags($topicID) 
{
    global $conn;

    $topicID = is_numeric($topicID) ? intval($topicID) : null;

    if ($topicID === null) {
        return False;
    }

    $sql = "DELETE FROM TopicTags WHERE TopicID = ?";
    $result = mysqli_execute_query($conn, $sql, [$topicID]);

    return ($result);
}

function DeleteTopicMembers($topicID)
{
    global $conn; 
    $topicID = is_numeric($topicID) ? intval($topicID) : null; 
    
    if ($topicID === null) { 
        return False; 
    } 

    $sql = "DELETE FROM TopicMembers WHERE TopicID = ?"; 
    $result = mysqli_execute_query($conn, $sql, [$topicID]); 
    
    return ($result);

}


function UpdateTask($taskID, $title = null, $description = null, $deadline = null, $status = null, $completedOn = null) : bool
{
    global $conn;

    $taskID = is_numeric($taskID) ? intval($taskID) : null;
    if($taskID === null ) {
        return False;
    }

    $title = CleanData($title);
    $description = CleanData($description);
    $deadline = CleanData($deadline);
    $status = CleanData($status);
    $completedOn = CleanData($completedOn);

    $params = [];
    $newValues = [];

    if (!empty($title)) {
        $newValues[] = "Title = ?";
        $params[] = $title;
    }

    if (!empty($description)) {
        $newValues[] = "Description = ?";
        $params[] = $description;
    }

    if (!empty($deadline)) {
        $newValues[] = "Deadline = ?";
        $params[] = $deadline;
    }

    if (!empty($status)) {
        $newValues[] = "Status = ?";
        $params[] = $status;

        if ($status === 'Complete') {
            $completedOn = date('Y-m-d'); // Set completedOn to current date when marking as complete

            $newValues[] = "CompletedOn = ?";
            $params[] = $completedOn;
        }
        else if ($status === 'Not Complete' || $status === 'Overdue') {
            $newValues[] = "CompletedOn = ?"; // Clear completedOn when marking as incomplete
            $params[] = null;
        }

    }



    if (empty($newValues)) {
        error_log("No fields to update for TaskID: " . $taskID);
        return False; // No fields to update
    }

    $params[] = $taskID; 

    $sql = "UPDATE Tasks SET " . implode(", ", $newValues) . " WHERE TaskID = ?";

    $result = mysqli_execute_query($conn, $sql, $params);

    return ($result);
}


function UpdateTopic($topicID, $title = null, $description = null) : bool
{
    global $conn;

    $topicID = is_numeric($topicID) ? intval($topicID) : null;
    if($topicID === null ) {
        return False;
    }

    $title = CleanData($title);
    $description = CleanData($description);

    $params = [];
    $newValues = [];

    if (!empty($title)) {
        $newValues[] = "Title = ?";
        $params[] = $title;
    }

    if (!empty($description)) {
        $newValues[] = "Description = ?";
        $params[] = $description;
    }

    if (empty($newValues)) {
        error_log("No fields to update for TopicID: " . $topicID);
        return False; // No fields to update
    }

    $params[] = $topicID;
    $sql = "UPDATE Topics SET " . implode(", ", $newValues) . " WHERE TopicID = ?";
    $result = mysqli_execute_query($conn, $sql, $params);

    return ($result);

}


function UpdatePost($postID, $title, $message, $file) {
    global $conn;

    if (!is_numeric($postID)) {
        return False;
    }

    $postID = intval($postID);
    $title = CleanData($title);
    $message = CleanData($message);
    $file = CleanData($file);
    
    $params = [];
    $newValues = [];

    if (!empty($title)) {
        $newValues[] = "Title = ?";
        $params[] = $title;
    }

    if (!empty($message)) {
        $newValues[] = "Message = ?";
        $params[] = $message;
    }

    if (!empty($file)) {
        $newValues[] = "File = ?";
        $params[] = $file;
    }

    if (empty($newValues)) {
        error_log("No fields to update for PostID: " . $postID);
        return False; // No fields to update
    }

    $params[] = $postID;
    $sql = "UPDATE Posts SET " . implode(", ", $newValues) . " WHERE PostID = ?";
    $result = mysqli_execute_query($conn, $sql, $params);

    return ($result);

} // Is this neccessaryy?


function UpdateNotification($notificationID, $title, $message) : bool
{
    global $conn;

    $notificationID = is_numeric($notificationID) ? intval($notificationID) : null;
    if($notificationID === null ) {
        return False;
    }

    $title = CleanData($title);
    $message = CleanData($message);

    $params = [];
    $newValues = [];

    if (!empty($title)) {
        $newValues[] = "Title = ?";
        $params[] = $title;
    }

    if (!empty($message)) {
        $newValues[] = "Message = ?";
        $params[] = $message;
    }

    if (empty($newValues)) {
        error_log("No fields to update for NotificationID: " . $notificationID);
        return False; // No fields to update
    }

    $params[] = $notificationID;
    $sql = "UPDATE Notifications SET " . implode(", ", $newValues) . " WHERE NotificationID = ?";
    $result = mysqli_execute_query($conn, $sql, $params);

    return ($result);

}




// SUCCESS: echo UpdateUser(3,  null, null, "Gavi", null, null) ? "User Updated" : "Failed to Update User";

// SUCCESS: echo UpdateProject(3, "Clean the whole house", null, null) ? "Project Updated" : "Failed to Update Project";

// SUCCESS: echo UpdateTask(12, "Updated Task Title", null, null, null) ? "Task Updated" : "Failed to Update Task";

// SUCCESS: echo UpdateTopic(7, "Brainmaxxing", null) ? "Topic Updated" : "Failed to Update Topic";

// SUCCESS: echo UpdatePost(2, "Can you explain what it is?", "Updated Post Message", null) ? "Post Updated" : "Failed to Update Post";

// SUCCESS: echo UpdateNotification(1, "Updated Notification Title", "Updated Notification Message") ? "Notification Updated" : "Failed to Update Notification";







?>