
<?php
function AddProjectMember($projectID,$userID, $leader) : bool 
{
    global $conn;
    $projectID = CleanData($projectID);
    $userID = CleanData($userID);
    $leader = CleanData($leader);
    

    $projectID = is_numeric($projectID) ? intval($projectID) : null;
    $userID = is_numeric($userID) ? intval($userID) : null;

    $leader = ($leader === 'true' || $leader === '1') ? 1 : 0;

    if($userID === null || $projectID === nuLL) {
        return False;
    }

    $sql = "INSERT INTO ProjectMembers (ProjectID, UserID, Leader) VALUES (?, ?, ?);";
    $result = mysqli_execute_query($conn, $sql, [$projectID, $userID, $leader]);
        


    return(bool)$result;



}

function AddProjectTag($projectID,$tagID) : bool  
{
    global $conn;

    $projectID = CleanData($projectID);
    $tagID = CleanData($tagID);

    $projectID = is_numeric($projectID) ? intval($projectID) : null;
    $tagID = is_numeric($tagID) ? intval($tagID) : null;

    if ($projectID === null || $tagID === null)
    {
        //error_log("DEBUG: Invalid owner or assignee ID");
        return False;
    }

    $sql = "INSERT INTO ProjectTags (ProjectID,TagID) VALUES (?,?);";
    $result = mysqli_execute_query($conn,$sql,[$projectID,$tagID]);

    if (!$result) {
        return False;

    }

    return (bool)$result;

}

function AddTopicMember($topicID, $userID) : bool 
{
    global $conn;
    $topicID = CleanData($topicID);
    $userID = CleanData($userID);
    

    $topicID = is_numeric($topicID) ? intval($topicID) : null;
    $userID = is_numeric($userID) ? intval($userID) : null;

    if($userID === null || $topicID === null) {
        return False;
    }

    $sql = "INSERT INTO TopicMembers (TopicID, UserID) VALUES (?, ?);";
    $result = mysqli_execute_query($conn, $sql, [$topicID, $userID]);

    return(bool)$result;
}

function AddTopicTag($topicID, $tagID) : bool  
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

}

?>