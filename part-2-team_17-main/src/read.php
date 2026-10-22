<?php
include "db-config.php";
include "update.php";


// NOTE:
// mysqli_fetch_assoc($result) - Returns a single row as an associative array
// mysqli_fetch_all($result, MYSQLI_ASSOC) - Returns all rows as an array of associative arrays

// Depreciated: Superseeded with GetActiveUsers() && GetInactiveUsers()
function GetUsers() : array|bool 
{
    global $conn;

    $sql = "SELECT * FROM Users";
    $result = mysqli_execute_query($conn, $sql);

    if ($users = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $users;
    }
    else {
        return False;
    }
}

function GetUser($userID) : array | bool
{
    global $conn;

    $sql = "SELECT * FROM Users WHERE UserID = ?";
    $result = mysqli_execute_query($conn, $sql, [$userID]);

    if ($users = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $users;
    }
    else {
        return False;
    }
}

function GetWorkers() : array|bool 
{
    global $conn;

    $sql = "SELECT UserID, Forename, Surname FROM Users WHERE UserType = 'Worker'";
    $result = mysqli_execute_query($conn, $sql);

    if ($users = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $users;
    }
    else {
        return False;
    }
}


function GetInactiveUsers() 
{
    global $conn;

    $sql = "SELECT * FROM Users WHERE UserType = 'Inactive'";
    $result = mysqli_execute_query($conn, $sql);

    if ($users = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $users;
    }
    else {
        return False;
    }
}

function GetActiveUsers() 
{
    global $conn;

    $sql = "SELECT * FROM Users WHERE UserType = 'Worker' OR UserType = 'Manager';";
    $result = mysqli_execute_query($conn, $sql);

    if ($users = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $users;
    }
    else {
        return False;
    }
}


function GetAllProjects() : array|bool 
{
    global $conn;

    $sql = "SELECT * FROM Projects";
    $result = mysqli_execute_query($conn, $sql);

    if ($projects = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        $today = date("Y-m-d");
        for ($i = 0; $i < count($projects); $i++) {
            if ($today > $projects[$i]['Deadline'] && $projects[$i]['Status'] != "Not Complete") {
                $projects[$i]['Status'] = "Overdue";

                $updateSql = "UPDATE Projects SET Status = 'Overdue' WHERE ProjectID = ?";
                $updateResult = mysqli_execute_query($conn, $updateSql, [$projects[$i]['ProjectID']]);

                if (!$updateResult) {
                    error_log("Failed to update project status for ProjectID: " . $projects[$i]['ProjectID']);
                }

            }
        }
        return $projects;
    }
    else {
        return False;
    }
}


function GetProjectData($projectID) : array|bool 
{
    global $conn;

    if (empty($projectID)) 
    {
        return False;
    }

    $sql = "SELECT *, 
                (SELECT COUNT(*) FROM Tasks WHERE ProjectID = Projects.ProjectID AND Status = 'Complete') AS TasksComplete,
                (SELECT COUNT(*) FROM Tasks WHERE ProjectID = Projects.ProjectID) AS TotalTasks
            FROM Projects WHERE ProjectID = ?";
    $result = mysqli_execute_query($conn, $sql, [$projectID]);

    if ($project = mysqli_fetch_all($result, MYSQLI_ASSOC))
    {
        return $project;
    }
    else {
        return False;
    }
}

function GetUserProjects($userID) : array|bool 
{
    global $conn;

    if (empty($userID)) {
        return False;
    }

    $sql = "SELECT *,
            (SELECT COUNT(*) FROM Tasks WHERE ProjectID = Projects.ProjectID AND Status = 'Complete') AS TasksComplete,
            (SELECT COUNT(*) FROM Tasks WHERE ProjectID = Projects.ProjectID) AS TotalTasks
     FROM Projects WHERE ProjectID IN (SELECT ProjectID FROM ProjectMembers WHERE UserID = ?)";
    $result = mysqli_execute_query($conn, $sql, [$userID]);

    if ($projects = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        $today = date("Y-m-d");
        for ($i = 0; $i < count($projects); $i++) {
            if ($today > $projects[$i]['Deadline'] && $projects[$i]['Status'] != "Not Complete") {
                $projects[$i]['Status'] = "Overdue";

                $updateSql = "UPDATE Projects SET Status = 'Overdue' WHERE ProjectID = ?";
                $updateResult = mysqli_execute_query($conn, $updateSql, [$projects[$i]['ProjectID']]);

                if (!$updateResult) {
                    error_log("Failed to update project status for ProjectID: " . $projects[$i]['ProjectID']);
                }

            }
        }
        return $projects;
    }
    else {
        return False;
    }

}

function GetProjectMembers($projectID) : array|bool 
{ // Get all members of a project
    global $conn;

    if (empty($projectID)) {
        return False;
    }

    $sql = "SELECT Users.*, ProjectMembers.Leader 
            FROM Users JOIN ProjectMembers ON Users.UserID = ProjectMembers.UserID 
            WHERE ProjectMembers.ProjectID = ?";
    $result = mysqli_execute_query($conn, $sql, [$projectID]);

    if ($members = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $members;
    }
    else {
        return False;
    }
}

function GetProjectTasks($projectID) : array|bool 
{
    global $conn;

    if (empty($projectID)) {
        return False;
    }

    $sql = "SELECT Tasks.*, Users.Forename, Users.Surname FROM Tasks JOIN Users ON Tasks.AssigneeID = Users.UserID WHERE ProjectID = ?";
    $result = mysqli_execute_query($conn, $sql, [$projectID]);

    if ($tasks = mysqli_fetch_all($result, MYSQLI_ASSOC))
    {
        $today = date("Y-m-d");
        for ($i = 0; $i < count($tasks); $i++) {
            if ($today > $tasks[$i]['Deadline'] && $tasks[$i]['Status'] != "Complete") {
                $tasks[$i]['Status'] = "Overdue";

                $updateSql = "UPDATE Tasks SET Status = 'Overdue' WHERE TaskID = ?";
                $updateResult = mysqli_execute_query($conn, $updateSql, [$tasks[$i]['TaskID']]);

                if (!$updateResult) {
                    error_log("Failed to update task status for TaskID: " . $tasks[$i]['TaskID']);
                }

            }
        }

        return $tasks;
    }
    else 
    {
        return False;
    }

}


function GetUserProjectTasks($userID, $projectID = null) : array|bool 
{ 
    global $conn;

    if (empty($userID)) {
        return False;
    }

    $all = ($projectID == null);

    if ($all)
    {
        $sql = "SELECT * FROM Tasks WHERE ProjectID IS NOT NULL AND AssigneeID = ?";
    }
    else
    {
        $sql = "SELECT * FROM Tasks WHERE ProjectID = ? AND AssigneeID = ?";
    }
    


    $result = mysqli_execute_query($conn, $sql, $all ? [$userID] : [$projectID, $userID]);

    if ($tasks = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        $today = date("Y-m-d");
        for ($i = 0; $i < count($tasks); $i++) {
            if ($today > $tasks[$i]['Deadline'] && $tasks[$i]['Status'] != "Complete") {
                $tasks[$i]['Status'] = "Overdue";

                $updateSql = "UPDATE Tasks SET Status = 'Overdue' WHERE TaskID = ?";
                $updateResult = mysqli_execute_query($conn, $updateSql, [$tasks[$i]['TaskID']]);

                if (!$updateResult) {
                    error_log("Failed to update task status for TaskID: " . $tasks[$i]['TaskID']);
                }

            }
        }
        return $tasks;
    }
    else {
        return False;
    }
}

function GetAllOverdueProjectTasks() : array|bool 
{
    global $conn;

    $sql = "SELECT Tasks.*, Users.Forename, Users.Surname FROM Tasks JOIN Users ON Tasks.AssigneeID = Users.UserID  AND Status = 'Overdue'";
    $result = mysqli_execute_query($conn, $sql, []);

    if ($tasks = mysqli_fetch_all($result, MYSQLI_ASSOC))
    {
        return $tasks;
    }
    else 
    {
        return False;
    }

}


function GetUserPersonalTasks($userID) : array|bool 
{ // Get all personal tasks for a user
    global $conn;

    if (empty($userID)) {
        return False;
    }

    $sql = "SELECT * FROM Tasks WHERE ProjectID IS NULL AND OwnerID = ?";
    $result = mysqli_execute_query($conn, $sql, [$userID]);

    if ($tasks = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        $today = date("Y-m-d");
        for ($i = 0; $i < count($tasks); $i++) {
            if ($today > $tasks[$i]['Deadline'] && $tasks[$i]['Status'] != "Complete") {
                $tasks[$i]['Status'] = "Overdue";

                $updateSql = "UPDATE Tasks SET Status = 'Overdue' WHERE TaskID = ?";
                $updateResult = mysqli_execute_query($conn, $updateSql, [$tasks[$i]['TaskID']]);

                if (!$updateResult) {
                    error_log("Failed to update task status for TaskID: " . $tasks[$i]['TaskID']);
                }

            }
        }
        return $tasks;
    }
    else {
        return False;
    }
}   



function GetTopicData($topicID) : array|bool 
{
    global $conn;

    if (empty($topicID)) 
    {
        //error_log("IncorrectID");
        return False;
    }

    $sql = "SELECT * FROM Topics WHERE TopicID = ?";
    $result = mysqli_execute_query($conn, $sql, [$topicID]);

    if ($topic = mysqli_fetch_all($result, MYSQLI_ASSOC))
    {
        return $topic;
    }
    else {
        return False;
    }
}

function GetUserTopics($userID) : array|bool 
{  
    global $conn;
    if (empty($userID)) {
        return False;
    }

    $sql = "SELECT * 
    FROM Topics INNER JOIN TopicMembers on Topics.TopicID = TopicMembers.TopicID 
    WHERE TopicMembers.UserID = ?;";
    $result = mysqli_execute_query($conn, $sql, [$userID]);

    if ($topics = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $topics;
    } else {
        return False;
    }

}

function GetTopicMembers($topicID) : array|bool 
{  
    global $conn;
    if (empty($topicID)) {
        return False;
    }

    $sql = "SELECT * 
    FROM Users INNER JOIN TopicMembers on Users.UserID = TopicMembers.UserID 
    WHERE TopicMembers.TopicID = ?;";
    $result = mysqli_execute_query($conn, $sql, [$topicID]);

    if ($topics = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $topics;
    } else {
        return False;
    }

}

function GetTopicPosts($topicID) : array|bool 
{  
    global $conn;
    if (empty($topicID)) {
        return False;
        }
    $sql = "SELECT * FROM Posts WHERE TopicID = ? AND ParentID IS NULL";
    $result = mysqli_execute_query($conn, $sql, [$topicID]);
    if ($posts = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $posts;
    } else {
        return False;
    }
}

function GetPostComments($postID) : array|bool 
{  
    global $conn;

    if (empty($postID)) {
        return False;
    }

    $sql= "SELECT Posts.*, Users.Forename, Users.Surname FROM Posts JOIN Users ON Posts.CreatedBy = Users.UserID WHERE ParentID = ?";
    $result = mysqli_execute_query($conn, $sql, [$postID]);

    if ($comments = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $comments;
    } else {
        return False;
    }
}

function GetPostData($postID) : array|bool 
{  
    global $conn;

    if (empty($postID)) {
        return False;
    }

    $sql = "SELECT Posts.*, Users.Forename, Users.Surname FROM Posts JOIN Users ON Posts.CreatedBy = Users.UserID WHERE PostID = ?";
    $result = mysqli_execute_query($conn, $sql, [$postID]);

    if ($post = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $post;
    } else {
        return False;
    }
}



function GetTags() : array|bool 
{
    global $conn;

    $sql = "SELECT * FROM Tags";
    $result = mysqli_execute_query($conn, $sql);

    if ($tags = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $tags;
    }
    else {
        return False;
    }
}

function GetProjectTags($projectID) : array|bool 
{
    global $conn;

    if (empty($projectID)) {
        return False;
    }

    $sql = "SELECT * FROM Tags WHERE TagID IN (SELECT TagID FROM ProjectTags WHERE ProjectID = ?)";
    $result = mysqli_execute_query($conn, $sql, [$projectID]);

    if ($tags = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $tags;
    }
    else {
        return False;
    }
}

function GetTopicTags($topicID) : array|bool 
{
    global $conn;
  
    if (empty($topicID)) {
        return False;
    }

    $sql = "SELECT * FROM Tags WHERE TagID IN (SELECT TagID FROM TopicTags WHERE TopicID = ?)";
    $result = mysqli_execute_query($conn, $sql, [$topicID]);

    if ($tags = mysqli_fetch_all($result, MYSQLI_ASSOC)) {
        return $tags;
    }
    else {
        return False;
    }
}



function GetUserNotifications($userID) : array|bool 
{
    global $conn;

    if (empty($userID)) {
        return False;
    }

    $sql = "SELECT * FROM Notifications 
    INNER JOIN NotificationRecipients on Notifications.NotificationID = NotificationRecipients.NotificationID
    WHERE ReceiverID = ? ORDER by SendDate DESC ";

    $results = mysqli_execute_query($conn, $sql, [$userID]);

    if ($notifications = mysqli_fetch_all($results, MYSQLI_ASSOC)) {
        return $notifications;
    } else {
        return False;
    }
}









//SUCCESS: echo LoginUser("sherwin@make-it-all.co.uk", "Password123!")['UserID'];

//SUCCESS: echo "user email" . GetUsers()[0]['Email'];

//SUCCESS: echo GetUserProjects(4)[0]['Title'];

//SUCCESS: echo GetProjectData(1)['Title'];

//SUCCESS:echo GetProjectTasks(1)[0]['Title'];

//SUCCESS: echo GetUserPersonalTasks(3)[0]['Title'];

//SUCCESS: echo GetProjectMembers(3)[0]['Forename'];

//SUCCESS: echo GetUserTopics(3)[0]['Title'];

//SUCCESS: echo GetTopicPosts(1)[0]['Title'];

//SUCCESS: echo GetPostComments(1)[0]['Title'];

//SUCCESS: echo GetTags()[0]['Name'];

//SUCCESS: echo GetProjectTags(1)[0]['Name'];

//SUCCESS: echo GetTopicTags(1)[0]['Name'];



//SUCCESS: echo GetUserNotifications(4)[0]['Message'];










?>