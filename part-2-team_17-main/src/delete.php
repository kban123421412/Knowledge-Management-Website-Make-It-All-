<?php 

include "db-config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    if (isset($_POST['action']))
    {
        $action = $_POST['action'];


        if ($action == "task")
        {
            $taskID =  isset($_POST['taskID']) ? $_POST['taskID'] : '';

            if (DeleteTask($taskID))
            {
                echo json_encode(["success" => true]);
            }
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to delete task."]);
            }
            exit;
        }
        else if ($action == "notification")
        {
            $notificationID = isset($_POST['notificationID']) ? $_POST['notificationID'] : '';

            if (DeleteNotification($notificationID))
            {
                echo json_encode(["success" => true]);
            }
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to delete notification."]);
            }
            exit;
        }
        else if ($action == "user")
        {
            $userID = isset($_POST['userID']) ? $_POST['userID'] : '';

            if (DeleteUser($userID))
            {
                $_SESSION['userID'] = -1;
                unset($_SESSION['username']); 
                unset($_SESSION['pageType']); 
                setcookie('project1', $_COOKIE['project1'], time() - (365*24*60*60));
                setcookie('project2', $_COOKIE['project2'], time() - (365*24*60*60));
                unset($_COOKIE['project1']);
                unset($_COOKIE['project2']);
                unset($_COOKIE['topic1']);
                unset($_COOKIE['topic2']);
                setcookie('topic1', $_COOKIE['topic1'], time() - (365*24*60*60));
                setcookie('topic2', $_COOKIE['topic2'], time() - (365*24*60*60));
                $_SESSION = [];
                session_destroy();
                echo json_encode(["success" => true]);
            }
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to delete user."]);
            }
            exit;
        }
        else if ($action == "project")
        {
            $projectID = isset($_POST['projectID']) ? $_POST['projectID'] : '';

            if (DeleteProject($projectID))
            {
                echo json_encode(["success" => true]);
            }
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to delete project."]);
            }
            exit;
        }
        else if ($action == "topic")
        {
            $topicID = isset($_POST['topicID']) ? $_POST['topicID'] : '';

            if (DeleteTopic($topicID))
            {
                echo json_encode(["success" => true]);
            }
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to delete topic."]);
            }
            exit;
        }
    }
}


function DeleteUser($userID) 
{

    global $conn;
    
    $userID = is_numeric($userID) ? intval($userID) : null;
    if($userID === null ) {
        return False;
    }
    $sql = "UPDATE Users
            SET UserType = 'Deleted'
            WHERE UserID = ?;";
    $result = mysqli_execute_query($conn, $sql, [$userID]);

    return ($result);
}

/* 
DeleteUser will delete:
- User
- Project Memberships
- Topic Memberships
- Notifications Sent & Received
*/


function DeleteProject($projectID) 
{
    global $conn ;
    $projectID = is_numeric($projectID) ? intval($projectID) : null;
    
    if($projectID === null ) {
        return False;
    }
    
    $sql = "DELETE FROM Projects WHERE ProjectID = ?";
    $result = mysqli_execute_query($conn, $sql, [$projectID]);
 

    return ($result);
}

/*
DeleteProject will delete:
- Project
- Project Memberships
- Project Tasks
- Project Tags
*/


function DeleteProjectMember($projectID, $userID) 
{
    global $conn ;

    $projectID = is_numeric($projectID) ? intval($projectID) : null;
    $userID = is_numeric($userID) ? intval($userID) : null;
    
    if($projectID === null || $userID === null ) {
        return False;
    }
    
    $sql = "DELETE FROM ProjectMembers WHERE ProjectID = ? AND UserID = ?";
    $result = mysqli_execute_query($conn, $sql, [$projectID, $userID]);

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


function DeleteTask($taskID) 
{
    global $conn ;
    $taskID = is_numeric($taskID) ? intval($taskID) : null;
    if($taskID === null ) {
        return False;
    }
    
    $sql = "DELETE FROM Tasks WHERE TaskID = ?";
    
    $result = mysqli_execute_query($conn, $sql, [$taskID]);

    return ($result);
}


function DeleteTopic($topicID) 
{ // REVIWED DB DESIGN delete related posts as well?
    global $conn ;
    $topicID = is_numeric($topicID) ? intval($topicID) : null;
    if($topicID === null ) {
        return False;
    }
    
    $sql = "DELETE FROM Topics WHERE TopicID = ?";
    
    $result = mysqli_execute_query($conn, $sql, [$topicID]);

    return ($result);
}

/*
DeleteTopic will delete:
- Topic
- Posts in the topic (+comments)
- Topic Memberships
- Topic Tags
*/
    

function DeletePost($postID) 
{ //Neede to edit to delete comments as well
    global $conn;
    $postID = is_numeric($postID) ? intval($postID) : null;
    
    if ($postID === null) {
        return False;
    } 
    
    $sql = "DELETE FROM Posts WHERE PostID = ?";
    
    $result = mysqli_execute_query($conn, $sql, [$postID]);

    return ($result);
    
      

}

/*
DeletePost will delete:
- Post
- Comments on the post
*/


function DeleteProjectTag($projectID, $tagID) 
{
    global $conn;

    $projectID = is_numeric($projectID) ? intval($projectID) : null;
    $tagID = is_numeric($tagID) ? intval($tagID) : null;

    if ($projectID === null || $tagID === null) {
        return False;
    }

    $sql = "DELETE FROM ProjectTags WHERE ProjectID = ? AND TagID = ?";
    $result = mysqli_execute_query($conn, $sql, [$projectID, $tagID]);

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



function DeleteTopicTag($topicID, $tagID) 
{
    global $conn;

    $topicID = is_numeric($topicID) ? intval($topicID) : null;
    $tagID = is_numeric($tagID) ? intval($tagID) : null;

    if ($topicID === null || $tagID === null) {
        return False;
    }

    $sql = "DELETE FROM TopicTags WHERE TopicID = ? AND TagID = ?";
    $result = mysqli_execute_query($conn, $sql, [$topicID, $tagID]);

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



function DeleteNotification($notificationID) 
{
    // On CASCADE DLEETE NO NEED TO DELETE RECIPIENTS INDIVIDUALLY
    global $conn;
    $notificationID = is_numeric($notificationID) ? intval($notificationID) : null;
    
    if ($notificationID === null) {
        return False;
    }
    
    $sql = "DELETE FROM Notifications WHERE NotificationID = ?;";

    $result = mysqli_execute_query($conn, $sql, [$notificationID]);

    return ($result);
}

/*
DeleteNotification will delete:
- Notification
- NotificationRecipients
*/


function DeleteNotificationRecipient($notificationID, $userID) 
{
    global $conn;

    $notificationID = is_numeric($notificationID) ? intval($notificationID) : null;
    $userID = is_numeric($userID) ? intval($userID) : null;

    if ($notificationID === null || $userID === null) {
        return False;
    }

    $sql = "DELETE FROM NotificationRecipients WHERE NotificationID = ? AND UserID = ?";
    $result = mysqli_execute_query($conn, $sql, [$notificationID, $userID]);

    return ($result);
}


// echo DeleteUser();
// echo DeleteProject();
// echo DeleteProjectMember(36, 4) ? "Project Member Deleted Successfully" : "Failed to Delete Project Member";





?>