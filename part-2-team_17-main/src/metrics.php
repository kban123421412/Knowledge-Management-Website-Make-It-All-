
<?php 

include "db-config.php";


/*

GetProjData in read.php already shows no. of tasks complete and no. of total tasks for each project
    Completion percentage calcualted from this



Number of tasks a user has not completed in a project
Number of tasks a user has completed in a project
Number of tasks a user has overdue in a project
Number of tasks a user has assigned to them in a project

    Calculate --> Standard deviation of completion of tasks of a user, based off of the the normal distribution-

*/

function GetUserProjectTaskStats($userID, $projectID = null) { //Can get user task stats for all projects or for a specific project
    global $conn;

    if (!is_numeric($userID)) 
    {
        return False;
    }

    if ($projectID !== null && !is_numeric($projectID)) 
    {
        return False;
    }

    $all = ($projectID == null);

    if ($all) 
    {
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN Status = 'Not Complete' THEN 1 ELSE 0 END), 0) AS NotCompleteTasks,
                COALESCE(SUM(CASE WHEN Status = 'Complete' THEN 1 ELSE 0 END), 0) AS CompleteTasks,
                COALESCE(SUM(CASE WHEN Status = 'Overdue' THEN 1 ELSE 0 END), 0) AS OverdueTasks,
                COUNT(*) AS TotalTasks
            FROM Tasks
            WHERE ProjectID IS NOT NULL AND AssigneeID = ?";
    }
    else 
    {
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN Status = 'Not Complete' THEN 1 ELSE 0 END), 0) AS NotCompleteTasks,
                COALESCE(SUM(CASE WHEN Status = 'Complete' THEN 1 ELSE 0 END), 0) AS CompleteTasks,
                COALESCE(SUM(CASE WHEN Status = 'Overdue' THEN 1 ELSE 0 END), 0) AS OverdueTasks,
                COUNT(*) AS TotalTasks
            FROM Tasks
            WHERE ProjectID = ? AND AssigneeID = ?";
    }
    

    $result = mysqli_execute_query($conn, $sql, $all ? [$userID] : [$projectID, $userID]);

    if ($project = mysqli_fetch_assoc($result))
    {
        return $project;
    }
    else {
        return False;
    }
}


function GetWeightedUserTaskCompletion($userID, $projectID = null) {//Can get weighted user task completion for all projects or for a specific project
    global $conn;

    if (!is_numeric($userID)) 
    {
        return False;
    }

    if ($projectID !== null && !is_numeric($projectID)) 
    {
        return False;
    }

    $all = ($projectID == null);


    if ($all) {
        $sql = "SELECT 
            COALESCE(SUM(Tasks.Priority), 0) AS CompletionScore
            FROM Tasks
            WHERE ProjectID IS NOT NULL AND AssigneeID = ? AND Status = 'Complete'";
    }
    else {
        $sql = "SELECT COALESCE(SUM(Tasks.Priority), 0) AS CompletionScore 
        FROM Tasks WHERE ProjectID = ? AND AssigneeID = ? AND Status = 'Complete'";
    }
    

    $result = mysqli_execute_query($conn, $sql, $all ? [$userID] : [$projectID, $userID]);

    if ($score = mysqli_fetch_assoc($result))
    {
        return $score['CompletionScore'];
    }
    else {
        return False;
    }
}


function GetAllEmployeeTaskCompletionAverage()  // Used to comapre against a user's task completion to the average task completion of all users in the company
{
    global $conn;
                                 // Coalesce converts NULL to 0 for users with no completed tasks
    $sql = "SELECT Users.UserID, COALESCE(SUM(Tasks.Priority), 0) AS CompleteTasks
            FROM Users LEFT JOIN Tasks ON Users.UserID = Tasks.AssigneeID AND Tasks.Status = 'Complete'
            GROUP BY Users.UserID"; // Only consider tasks that are assigned to users

    $result = mysqli_execute_query($conn, $sql);

    $totalTasksCompleted = 0;
    $totalUsers = 0;
    $sumOfSquares = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $totalTasksCompleted += $row['CompleteTasks'];
        $totalUsers += 1;

        $sumOfSquares += pow($row['CompleteTasks'], 2);

    }

    $averageTasksCompleted = $totalTasksCompleted / $totalUsers;

    $variance = ($sumOfSquares / $totalUsers) - pow($averageTasksCompleted, 2);

    $standardDeviation = sqrt($variance);


    // Round number ?
    $averageTasksCompleted = round($averageTasksCompleted, 2);
    $standardDeviation = round($standardDeviation, 2);


    return ["average" => $averageTasksCompleted, 
            "standardDeviation" => $standardDeviation];
}



function GetProjectTaskStats($projectID = null) {// can get task stats for all projects or for a specific one
    global $conn;

    if (!is_numeric($projectID) && $projectID !== null) 
    {
        return False;
    }

    $all = ($projectID == null);

    if ($all)
    {
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN Status = 'Not Complete' THEN 1 ELSE 0 END), 0) AS NotCompleteTasks,
                COALESCE(SUM(CASE WHEN Status = 'Complete' THEN 1 ELSE 0 END), 0) AS CompleteTasks,
                COALESCE(SUM(CASE WHEN Status = 'Overdue' THEN 1 ELSE 0 END), 0) AS OverdueTasks
            FROM Tasks
            WHERE ProjectID IS NOT NULL";
    }
    else {
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN Status = 'Not Complete' THEN 1 ELSE 0 END), 0) AS NotCompleteTasks,
                COALESCE(SUM(CASE WHEN Status = 'Complete' THEN 1 ELSE 0 END), 0) AS CompleteTasks,
                COALESCE(SUM(CASE WHEN Status = 'Overdue' THEN 1 ELSE 0 END), 0) AS OverdueTasks
            FROM Tasks
            WHERE ProjectID = ?";
    }

    $result = mysqli_execute_query($conn, $sql, $all ? [] : [$projectID]);

    if ($data = mysqli_fetch_assoc($result))
    {
        return $data;
    }
    else {
        return False;
    }


}


function GetProjectTaskCompletionOverMonths($projectID) 
{
    global $conn;

    $datesSql = "SELECT DATE_FORMAT(CreatedOn, '%Y-%m') AS StartDate, DATE_FORMAT(Deadline, '%Y-%m') AS ProposedDeadline, Status 
    FROM Projects WHERE ProjectID = ?";

    $expectedTasksSql = "SELECT DATE_FORMAT(Deadline, '%Y-%m') AS Month, COUNT(*) AS ExpectedTasks
            FROM Tasks
            WHERE ProjectID = ?
            GROUP BY Month
            ORDER BY Month ASC";

    $actualTasksSql = "SELECT DATE_FORMAT(CompletedOn, '%Y-%m') AS Month, COUNT(*) AS ActualTasks
            FROM Tasks
            WHERE ProjectID = ? AND Status = 'Complete'
            GROUP BY Month
            ORDER BY Month ASC";

    $datesResult = mysqli_execute_query($conn, $datesSql, [$projectID]);

    $dates = $datesResult ? mysqli_fetch_assoc($datesResult) : null;

    $startDate = new DateTime($dates['StartDate']);
    $proposedDeadline = new DateTime($dates['ProposedDeadline']);

    $overdue = ($dates['Status'] == 'Overdue');

    $today = new DateTime();

    $endPoint = ($overdue ? $today : $proposedDeadline); // Ensure end point does not go beyond current date

    $interval= $startDate ->diff($endPoint);

    $monthsBetween = ($interval->y * 12) + $interval->m; // Total months between start and proposed deadline

    $expectedTasksResult = mysqli_execute_query($conn, $expectedTasksSql, [$projectID]);

    $actualTasksResult = mysqli_execute_query($conn, $actualTasksSql, [$projectID]);


    $expectedTasks = [];
    while ($row = mysqli_fetch_assoc($expectedTasksResult)) {
        $expectedTasks[$row['Month']] = $row['ExpectedTasks'];
    }
    

    $actualTasks = [];
    while ($row = mysqli_fetch_assoc($actualTasksResult)) {
        $actualTasks[$row['Month']] = $row['ActualTasks'];
    }

    $allExpectedTasks = [];
    $allActualTasks = [];


    for ($i = 0; $i <= $monthsBetween; $i++) 
    {
        $month = date('Y-m', strtotime("+$i month", strtotime($dates['StartDate'])));

        $allExpectedTasks[$month] = (int)($expectedTasks[$month] ?? 0);
        $allActualTasks[$month] = (int)($actualTasks[$month] ?? 0);

    }

    return ["expectedTasksCompletion" => $allExpectedTasks, "actualTasksCompletion" => $allActualTasks];


} // This will form the expected vs actual graph


function GetCompanyTaskCompletionOverYear($year)
{
    global $conn;

    //Check year is valid
    if (empty($year) || !is_numeric($year)) {
        return False;
    }
    if ($year < 2020 || $year > date("Y") + 1) {
        return False;
    }




    $months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]; 

    $allExpectedTasks = [];
    $allActualTasks = [];



    $expectedSQL = "SELECT DATE_FORMAT(Deadline, '%Y-%m') AS Month, COUNT(*) AS ExpectedTasks
            FROM Tasks
            WHERE ProjectID IS NOT NULL AND YEAR(Deadline) = ?
            GROUP BY Month
            ORDER BY Month ASC";

    $actualSQL = "SELECT DATE_FORMAT(CompletedOn, '%Y-%m') AS Month, COUNT(*) AS ActualTasks
            FROM Tasks
            WHERE ProjectID IS NOT NULL AND YEAR(CompletedOn) = ? AND Status = 'Complete'
            GROUP BY Month
            ORDER BY Month ASC";


    $expectedTasksResult = mysqli_execute_query($conn, $expectedSQL, [$year]);

    $actualTasksResult = mysqli_execute_query($conn, $actualSQL, [$year]);


    $expectedTasks = [];
    while ($row = mysqli_fetch_assoc($expectedTasksResult)) {
        $expectedTasks[$row['Month']] = $row['ExpectedTasks'];
    }
    

    $actualTasks = [];
    while ($row = mysqli_fetch_assoc($actualTasksResult)) {
        $actualTasks[$row['Month']] = $row['ActualTasks'];
    }

    for ($i = 0; $i < 12; $i++) 
    {
        $month = date('Y-m', strtotime("+$i month", strtotime("$year-01-01")));

        if (!isset($expectedTasks[$month])) {
            $allExpectedTasks[$months[$i]] = 0; // Set expected tasks to 0 for months with no tasks
        }
        else {
            $allExpectedTasks[$months[$i]] = (int)$expectedTasks[$month]; // Convert to integer
        }

        if (!isset($actualTasks[$month])) {
            $allActualTasks[$months[$i]] = 0; // Set actual tasks to 0 for months with no completed tasks
        }
        else {
            $allActualTasks[$months[$i]] = (int)$actualTasks[$month]; // Convert to integer
        }

    }


    return ["expectedTasksCompletion" => $allExpectedTasks, "actualTasksCompletion" => $allActualTasks];

}











/*

Company Wide Metrics:
    Number of active, overdue and completed projects in the company
    Number of active project tasks in the company
    Number of users in the company

*/

function GetCompanyMetrics() {
    global $conn;

    $sql = "SELECT 
                (SELECT COUNT(*) FROM Projects WHERE Status = 'Not Complete') AS ActiveProjects,
                (SELECT COUNT(*) FROM Projects WHERE Status = 'Complete') AS CompletedProjects,
                (SELECT COUNT(*) FROM Projects WHERE Status = 'Overdue') AS OverdueProjects,
                (SELECT COUNT(*) FROM Users) AS EmployeeCount,
                (SELECT COUNT(*) FROM Posts WHERE ParentID IS NULL) AS TotalPosts";


    $result = mysqli_execute_query($conn, $sql);

    if ($metrics = mysqli_fetch_assoc($result))
    {
        return $metrics;
    }
    else {
        return False;
    }
}











// Detect imblanaced loads and insufficient resourcing in projects
    // This is to oinform manager to act on this and reallocate resources to ensure project is completed on time

    // Perhaps implement som ind of estimating or prediciton, UNLIKELY




/* $stats = GetProjectTaskCompletionOverMonths(1);

echo "<pre>";
print_r($stats);
 */



?>
