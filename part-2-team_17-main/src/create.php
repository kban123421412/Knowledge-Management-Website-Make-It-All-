<?php
include "db-config.php";
include "function.php";

function CleanData($data): string 
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);

    return $data;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    // Route based on an argument
    if (isset($_POST['action']))
    {
        $action = $_POST['action'];

        if ($action == 'task')
        {
            $tasktTitle = $_POST['create-task-title'];
            $projectID =  isset($_POST['projectID']) ? $_POST['projectID'] : '';
            $ownerID =  isset($_POST['ownerID']) ? $_POST['ownerID'] : '';
            $assigneeID =  isset($_POST['user']) ? $_POST['user'] : '';

            $description =  isset($_POST['description']) ? $_POST['description'] : '';
            $priority =  isset($_POST['priority']) ? $_POST['priority'] : '';
            $deadline =  isset($_POST['deadline']) ? $_POST['deadline'] : '';

            if (CreateTask($projectID, $ownerID, $assigneeID, $tasktTitle, $description, $priority, $deadline))
            {
                echo json_encode(["success" => true]);
            }
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to create task."]);
            }
            exit;
        }
        else if ($action == 'project')
        {
            $projectTitle = isset($_POST['title']) ? $_POST['title'] : '';
            $projectDeadline = isset($_POST['deadline']) ? $_POST['deadline'] : '';
            $projectDescription = isset($_POST['description']) ? $_POST['description'] : '';

            $projectID = CreateProject($projectTitle, $projectDescription, $projectDeadline);

            if ($projectID !== false && is_numeric($projectID))
            {
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
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to create project."]);
            }
        }
        else if ($action == 'knowledge')
        {
            $topicTitle = isset($_POST['title']) ? $_POST['title'] : '';
            $topicOwner = isset($_POST['owner']) ? $_POST['owner'] : '';

            $knowledgeID = CreateTopic($topicTitle, $topicOwner);

            if ($knowledgeID !== false && is_numeric($knowledgeID))
            {
                $tags = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : [];
                
                foreach ($tags as $tag)
                {
                    if (is_numeric($tag))
                    {
                        AddTopicTag($knowledgeID,intval($tag));
                    }
                }

                $users = isset($_POST['users']) && is_array($_POST['users']) ? $_POST['users'] : [];

                foreach ($users as $user)
                {
                    if (is_numeric($user))
                    {
                        AddTopicMember($knowledgeID, $user);
                    }
                }

                echo json_encode(["success" => true]);
            } 
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to create knowledge topic."]);
            }
        }
        else if ($action == "post")
        {
            $postTitle = isset($_POST['title']) ? $_POST['title'] : '';
            $postMessage = isset($_POST['message']) ? $_POST['message'] : '';

            $parentID = isset($_POST['parentID']) ? $_POST['parentID'] : '';
            $topicID = isset($_POST['topicID']) ? $_POST['topicID'] : '';
            $ownerID = isset($_POST['ownerID']) ? $_POST['ownerID'] : '';

            $file_path_for_db = null;

            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) { //Check that file was uploaded without errors
                $uploadDir = '/var/www/html/pakms-uploads/'; 
                
                $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'txt'];
                $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

                if (!in_array($fileExtension, $allowedExtensions)) {
                    echo json_encode(["success" => false, "error" => "Invalid file type. Allowed types: " . implode(", ", $allowedExtensions)]);
                    exit;
                }

                $fileName = time() . '_' . basename($_FILES['file']['name']);
                $targetFile = $uploadDir . $fileName;

                // move the file from temp storage to your folder
                if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
                    $filePath = $fileName; // Store just the name in the DB, since we know the path
                }
                else
                {
                    error_log("Failed to move file to: " . $targetFile);
                    echo json_encode(["success" => false, "error" => "Server could not save the file. Check permissions."]);
                    exit;
                }
            }

            if (CreatePost($parentID, $topicID, $postTitle, $postMessage, $filePath, $ownerID))
            {
                echo json_encode(["success" => true]);
            }
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to create post."]);
            }

        }
        else if ($action == "register")
        {
            $email =  isset($_POST['email']) ? $_POST['email'] : '';
            $password =  isset($_POST['password']) ? $_POST['password'] : '';
            $fname = isset($_POST['forename']) ? $_POST['forename'] : '';
            $sname = isset($_POST['surname']) ? $_POST['surname'] : '';

            error_log("$email, $password, $fname, $sname");
           
            if (RegisterUser($email, $password, $fname, $sname, 'Inactive'))
            {
                echo json_encode(["success" => true]);
            } 
            else
            {
                echo json_encode(["success" => false, "error" => "Failed to send registration request."]);
            }
            exit;
        }
        
    }
} 

function RegisterUser($email, $password, $forename, $surname, $userType) : bool //|int 
{
    global $conn;

    if (empty($email) || empty($password) || empty($forename) || empty($surname) || empty($userType)) {
        return False;
    }

    // trim + stripSlashes + htmlspecialchars
    $email = CleanData($email);
    //$password = CleanData($password);
    $forename = CleanData($forename);
    $surname = CleanData($surname);
    $userType = CleanData($userType);

    //Check email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return False;
    }
    else if (!str_ends_with($email, "@make-it-all.co.uk")) {
        return False;
    }

    $password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO Users (Email, Password, Forename, Surname, UserType) VALUES (?, ?, ?, ?, ?);";

    $result = mysqli_execute_query($conn, $sql, [$email, $password, $forename, $surname, $userType ]);

    return $result; 
    
}


function CreateProject($title,$description,$deadline) : int|bool 
{
    global $conn;
    
    if(empty($title) || empty($deadline)) {
        return False;
    } 

    $title = CleanData($title); 
    $description = CleanData($description);
    // $createdOn = CleanData($createdOn);
    $deadline = CleanData($deadline);


    $createdOn = date("Y-m-d");// set todays date
    $d = DateTime::createFromFormat('Y-m-d', $deadline);
    if (!($d && $d->format('Y-m-d') === $deadline)) {
        //error_log("DEBUG: Invalid deadline format");
        return False;
    }
    
    $sql = "INSERT INTO Projects (Title, Description,Status, CreatedOn, Deadline) VALUES (?, ?, 'Not Complete', ?, ?);";

    $results = mysqli_execute_query($conn, $sql, [$title, $description, $createdOn, $deadline]); 

    $projectID = mysqli_insert_id($conn);

    return $results ? $projectID : False;



}





function CreateTask($projectID, $ownerID, $assigneeID, $title, $description, $priority, $deadline) : bool 
{
    //error_log("DEBUG CreateTask: ProjectID=['$projectID'] OwnerID=['$ownerID'] AssigneeID=['$assigneeID']");
    global $conn;

    if (empty($ownerID) || empty($assigneeID) || empty($title) || empty($priority) || empty($deadline)) 
    {
        //error_log("DEBUG: empty field");
        return False;
    }

    $ownerID = CleanData($ownerID);
    $assigneeID = CleanData($assigneeID);
    $title = CleanData($title);
    $description = CleanData($description);
    $priority = CleanData($priority);
    $deadline = CleanData($deadline);


    $projectID = is_numeric($projectID) ? intval($projectID) : null; // projectID can be null for personal tasks
    
    $ownerID = is_numeric($ownerID) ? intval($ownerID) : null;
    $assigneeID = is_numeric($assigneeID) ? intval($assigneeID) : null;

    if ($ownerID === null || $assigneeID === null) 
    {
        //error_log("DEBUG: Invalid owner or assignee ID");
        return False;
    }
    
    if (is_numeric($priority))
    {
        $priority = intval($priority);
        if ($priority < 1 || $priority > 3) 
        {
            //error_log("DEBUG: Invalid priority value");
            return False;
        }
    }
    else 
    {
        //error_log("DEBUG: Priority is not numeric");
        return False;
    }
    

    $createdOn = date("Y-m-d");// set todays date

    $d = DateTime::createFromFormat('Y-m-d', $deadline);
    if (!($d && $d->format('Y-m-d') === $deadline)) 
    {
        //error_log("DEBUG: Invalid deadline format");
        return False;
    }
    
    //error_log("DEBUG: Error Checking Complete.");
    
    $sql = "INSERT INTO Tasks (ProjectID, OwnerID, AssigneeID, Title, Description, Status, Priority, CreatedOn, Deadline) 
            VALUES (?, ?, ?, ?, ?, 'Not Complete', ?, ?, ?)";

    
    $result = mysqli_execute_query($conn, $sql, [
        $projectID,   
        $ownerID, 
        $assigneeID, 
        $title, 
        $description, 
        $priority, 
        $createdOn, 
        $deadline
    ]);


    if ($result && $projectID !== null) {
        
        $notificationMade = CreateNotification($ownerID, "New Task Assigned: $title", "You have been assigned a new task: $title with deadline $deadline");

        if ($notificationMade) {
            $notificationID = mysqli_insert_id($conn);
            $notificationReceived = AddNotificationRecipients($notificationID, $assigneeID);

            if ($notificationReceived) {

                return True;
            }
            else {
                error_log("DEBUG: Failed to add notification recipient");
                return False;
            }


        }
        else {
            //error_log("DEBUG: Failed to create notification");
            return False;
        }

    }
    else if ($result)
    {
        return True;
    }
    else {
        //error_log("DEBUG: Failed to create task");
        return False;

    }
}


function CreateTopic($title,$createdBy) : bool|int
{
    global $conn;
    $title = CleanData($title);
    $createdBy = CleanData($createdBy);

    if (empty($title) || empty($createdBy)) {
        return False;
    }

    $createdBy = is_numeric($createdBy) ? intval($createdBy) : null;
    if ($createdBy === null)
    {
        //error_log("DEBUG: Invalid owner or assignee ID");
        return False;
    }

    $sql = "INSERT INTO Topics (Title, CreatedBy) VALUES (?, ?);";
    $result = mysqli_execute_query($conn, $sql, [$title, $createdBy]);

    $topicID = mysqli_insert_id($conn);

    return $result ? $topicID : False;
    
}


function CreatePost($parentID, $topicID, $title, $message, $file, $createdBy) 
{

    //error_log("DEBUG CreateTask: ProjectID=['$projectID'] OwnerID=['$ownerID'] AssigneeID=['$assigneeID']");
    global $conn;

    if (empty($topicID) || empty($title)|| empty($createdBy)) 
    {
        //error_log("DEBUG: empty field");
        return False;
    }

    $parentID = CleanData($parentID);
    $topicID = CleanData($topicID);
    $title = CleanData($title);
    $message = CleanData($message);
    $file = CleanData($file);
    $createdBy = CleanData($createdBy);


    if ($parentID == 'null' || $parentID == "" || $parentID == NULL) 
    { //Check back later
        $parentID = NULL;
    }
    else if (is_numeric($parentID))
    {
        $parentID = intval($parentID);
    }
    else 
    {
        //error_log("DEBUG: Invalid parent ID");
        return False;
    }

    $topicID = is_numeric($topicID) ? intval($topicID) : null;
    

    if ($topicID === null || $createdBy === null)
    {
        //error_log("DEBUG: Invalid owner or assignee ID");
        return False;
    }

    $message = ($message == "null" || $message == "" || $message == NULL) ? NULL : $message;
    $file = ($file == "null" || $file == "" || $file == NULL) ? NULL : $file;
    $createdBy = is_numeric($createdBy) ? intval($createdBy) : null;

    $createdOn = date("Y-m-d");// set todays date
    
   // error_log("DEBUG: Error Checking Complete.");






    
    $sql = "INSERT INTO Posts (ParentID, TopicID, Title, Message, File, CreatedBy, CreatedOn) 
    VALUES (?, ?, ?, ?, ?, ?, ?);";


    $result = mysqli_execute_query($conn, $sql, [$parentID, $topicID, $title, $message, $file, $createdBy, $createdOn]);

    $postID = mysqli_insert_id($conn);

    return $result ? $postID : False;

}

/* function AddTopicTag($topicID,$tagID) : bool  
{
    global $conn;

    $topicID = CleanData($topicID);
    $tagID = CleanData($tagID);
    

    $topicID = is_numeric($topicID) ? intval($topicID) : null;
    $tagID = is_numeric($tagID) ? intval($tagID) : null;

    if ($topicID === null || $tagID === null)
    {
        //error_log("DEBUG: Invalid owner or assignee ID");
        return False;
    }

    $sql = "INSERT INTO TopicTags (TopicID,TagID) VALUES (?,?);";
    $result = mysqli_execute_query($conn,$sql,[$topicID,$tagID]);


    if (!$result) {
        return False;
    }

    return (bool)$result;

} */


function CreateNotification($senderID,$title,$message) : bool 
{
    global $conn ; 
   
    $senderID = CleanData($senderID);
    $sendDate = date("Y-m-d");   // Change format if needed, check back later
    $title = CleanData($title);
    $message = CleanData($message);
  
    if (empty($senderID) || empty($title)) {
        return False;
    }

    $senderID = is_numeric($senderID) ? intval($senderID) : null;
    if ($senderID === null)
    {
        //error_log("DEBUG: Invalid owner or assignee ID");
        return False;
    }


    if ($message == "null" || $message == "" || $message == NULL) {
        $message = Null;
    }

    $sql = "INSERT INTO Notifications (SenderID, SendDate, Title, Message) VALUES (?, ?, ?, ?);";
    $result = mysqli_execute_query($conn, $sql, [$senderID, $sendDate, $title, $message]);

    if (!$result) {
        return false;
    }

    return (bool)$result;
}

function AddNotificationRecipients($notifcationID,$recieverID) : bool 
{
    global $conn ; 
    $notifcationID = CleanData($notifcationID);
    $recieverID = CleanData($recieverID);

    $notifcationID = is_numeric($notifcationID) ? intval($notifcationID) : null;
    $recieverID = is_numeric($recieverID) ? intval($recieverID) : null;
    if ($notifcationID === null || $recieverID === null)
    {
        //error_log("DEBUG: Invalid owner or assignee ID");
        return False;
    }

    $sql = "INSERT INTO NotificationRecipients (NotificationID, ReceiverID) VALUES (?, ?);";
    $result = mysqli_execute_query($conn, $sql, [$notifcationID, $recieverID]);

    if (!$result) {
        return false;

    }

    return (bool)$result;

        	
}



//echo RegisterUser("bonkers@make-it-all.co.uk", "haha", "Hello", "World", "Manager") ? "User Registered Successfully" : "User Registration Failed";


//SUCCESS: echo CreateProject("Clean the house", "Kitchen, Counter & Hobs, Living Room", "2026-02-08") ? "Project Created Successfully" : "Project Creation Failed";

//SUCCESS: echo AddProjectMember(3, 4) ? "Project Member Added Successfully" : "Failed to Add Project Member";

//SUCCESS: echo CreateTask(1, 3, 4, "Test Task", "This is a test task", 2, "2026-12-31") ? "Task Created Successfully" : "Task Creation Failed";

//SUCCESS: echo CreateTask(1, 3, 4, "ANother Test Task", "This is a test task after implementation of notification creation", 3, "2026-06-24") ? "Task Created Successfully" : "Task Creation Failed";

//SUCCESS: echo CreateTask('null', 3, 3, "Personal Task", "This is a personal task", 1, "2026-11-30") ? "Personal Task Created Successfully" : "Personal Task Creation Failed";


//SUCCESS: echo CreateTopic("Chudwork", 4) ? "Topic Created Successfully" : "Topic Creation Failed";

//SUCCESS:echo CreatePost(Null, 1, "Chudmaxxing", "How to chudmax", null, 4) ? "Post Created Successfully" : "Post Creation Failed";

//SUCCESS:echo CreatePost(1, 1, "why chudmax?", null, null, 4) ? "Comment Created Successfully" : "Comment Creation Failed";


//SUCCESS: echo AddProjectTag(1, 1) ? "Project Tag Added Successfully" : "Failed to Add Project Tag";

//SUCCESS: echo AddTopicTag(1, 3) ? "Topic Tag Added Successfully" : "Failed to Add Topic Tag";



//SUCCESS: echo CreateNotification(3, "Test Notification", "This is a test notification") ? "Notification Created Successfully" : "Notification Creation Failed";

//SUCCESS: echo AddNotificationRecipients(1, 4) ? "Notification Recipient Added Successfully" : "Failed to Add Notification Recipient";






//echo RegisterUser("joseph.fisher@make-it-all.co.uk", "bigNround", "Joseph", "Fisher", "Inactive") ? "Success" : "No Success";











//mysqli_close($conn); PHP auto closes connections at the end of the script

//echo password_hash("Gavison7", PASSWORD_DEFAULT);

?>