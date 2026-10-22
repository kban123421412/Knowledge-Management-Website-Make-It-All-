<?php

include "read.php";
include "metrics.php";

session_start();

// Check if user is logged in. // -1 == No User
// -1, 4, ---
if (!isset($_SESSION['userID'])) {
    $_SESSION['userID'] = -1;
    header("Location: dashboard.php");
    exit;
}
if (
    $_SESSION['userID'] == -1 || 
    !isset($_SESSION['userType']) ||
    !isset($_SESSION['username'])
)
{
    // Handle login.

}


$pageType = "dashboard";
if (isset($_GET['page-type']) && $_GET['page-type'] == "settings")
{
    $pageType = "settings";
}

$targetUserID = null;
$targetProjectID = null;
$targetYear = date("Y"); 



if ($_SESSION['userID'] != -1)
{
/*
---------------------

Data Fetch

--------------------
*/
// Cookie Management
// Project 1 Cookie.
if (isset($_COOKIE["project1"]))
{
    $project1ID = $_COOKIE["project1"];
    $project1ID = intval($project1ID);

    $project1Data = GetProjectData($project1ID);
    if (isset($project1Data[0]))
    {
        $project1Data = $project1Data[0];
    }
}
else  
{    
    $userProjects = GetUserProjects($_SESSION["userID"]);
    if ($userProjects && isset($userProjects[0])) 
    {    
        $project1Data = $userProjects[0];
        $project1ID = $project1Data['ProjectID'];
    } else {
        $project1Data = null;
        $project1ID = null;
    }
}
// Project 2 Cookie.
if (isset($_COOKIE["project2"]))
{
    $project2ID = $_COOKIE["project2"];
    $project2ID = intval($project2ID);

    $project2Data = GetProjectData($project2ID);
    if (isset($project2Data[0]))
    {
        $project2Data = $project2Data[0];
    }
}
else  
{
    $userProjects = GetUserProjects($_SESSION["userID"]);
    if ($userProjects && isset($userProjects[1])) 
    {    
        $project2Data = $userProjects[1];
        $project2ID = $project2Data['ProjectID'];
    } else {
        $project2Data = null;
        $project2ID = null;
    }
}

// Knowledge 1 Cookie
if (isset($_COOKIE["topic1"]))
{
    $topic1ID = $_COOKIE["topic1"];
    $topic1ID = intval($topic1ID);

    $topic1Data = GetTopicData($topic1ID);
    if (isset($topic1Data[0]))
    {
        $topic1Data = $topic1Data[0];
    }
}
else  
{    
    $userTopics = GetUserTopics($_SESSION["userID"]);
    if ($userTopics && isset($userTopics[0])) 
    {    
        $topic1Data = $userTopics[0];
        $topic1ID = $topic1Data['TopicID'];
    } else {
        $topic1Data = null;
        $topic1ID = null;
    }
}
// Knowledge 2 Cookie.
if (isset($_COOKIE["topic2"]))
{
    $topic2ID = $_COOKIE["topic2"];
    $topic2ID = intval($topic2ID);

    $topic2Data = GetTopicData($topic2ID);
    if (isset($topic2Data[0]))
    {
        $topic2Data = $topic2Data[0];
    }
}
else  
{
    $userTopics = GetUserTopics($_SESSION["userID"]);
    if ($userTopics && isset($userTopics[1])) 
    {    
        $topic2Data = $userTopics[1];
        $topic2ID = $topic2Data['TopicID'];
    } else {
        $topic2Data = null;
        $topic2ID = null;
    }
}

// Pull & Process Metric Data

$employeeTaskCompletion = GetAllEmployeeTaskCompletionAverage();
$employeeTaskCompletionAverage = $employeeTaskCompletion['average']; 
$employeeTaskCompletionStandardDeviation = $employeeTaskCompletion['standardDeviation'];

$standardDeviationLimit = -1; // Adjust standard deviation limit for training suggestion

$targetUserID = null;

if ($_SESSION['userType'] === "Worker")
{
    $targetUserID = $_SESSION["userID"]; 
}

else if ($_SESSION['userType'] === "Manager")
{

    $companyMetrics = GetCompanyMetrics();
    if (!$companyMetrics) 
    {   // Set metrics to null if query fails
        $activeProjects = null;
        $completedProjects = null;
        $employeeCount = null;
        $postCount = null;
    }
    else 
    {
        $activeProjects = $companyMetrics['ActiveProjects'];
        $completedProjects = $companyMetrics['CompletedProjects'];
        $employeeCount = $companyMetrics['EmployeeCount'];
        $postCount = $companyMetrics['TotalPosts'];
    }
    
    $allProjectTaskStats = GetProjectTaskStats(null);

    if (!$allProjectTaskStats) 
    {   // Set task stats to 0 if query fails, to avoid calucation with null
        $allProjectTaskStats = [ 
            'NotCompleteTasks' => 0,
            'CompleteTasks' => 0,
            'OverdueTasks' => 0
        ];
    }

    $allNotCompletedTasks = $allProjectTaskStats['NotCompleteTasks'];
    $allCompletedTasks = $allProjectTaskStats['CompleteTasks'];
    $allOverdueTasks = $allProjectTaskStats['OverdueTasks'];
    $allTotalTasks = $allNotCompletedTasks + $allCompletedTasks + $allOverdueTasks;

    $allOverdueProjectTasks = GetAllOverdueProjectTasks();


    $allProjects = GetAllProjects();

    if (!is_array($allProjects)) {$allProjects = [];}

    $allProjects = array_column($allProjects, null, 'ProjectID');

    if (isset($_GET['view_project'])) 
    {
        $targetProjectID = intval($_GET['view_project']);
    }
    else 
    {
        $targetProjectID = null;
    }


    //Employee Overview
    //Able to search through employees and obtain their data and view worker geenral for all employees

    $allWorkers = GetActiveUsers();

    if (!is_array($allWorkers)) {$allWorkers = [];}

    $allWorkers = array_column($allWorkers, null, 'UserID');

    if (isset($_GET['view_employee'])) 
    {
        $targetUserID = intval($_GET['view_employee']);
    } 


    $employeesForTraining = [];

    foreach ($allWorkers as $worker) 
    {
        $workerID = $worker['UserID'];

        $workerScore = GetWeightedUserTaskCompletion($workerID, null);

        $performanceDiff = 0;
        if ($employeeTaskCompletionStandardDeviation > 0) 
        {
            $performanceDiff = ($workerScore - $employeeTaskCompletionAverage) / $employeeTaskCompletionStandardDeviation; // Calculate how many standard deviations the user's completion is from the average
        }

        if ($performanceDiff < $standardDeviationLimit) // If the worker is more than 1 standard deviation below the average, add them to the training list
        {
            $workerStats = GetUserProjectTaskStats($workerID, null);

            if ($workerStats['TotalTasks'] > 0) 
            {
                $worker['PerformanceDiff'] = $performanceDiff;
                $employeesForTraining[] = $worker;
            }
        }

    }




    usort($employeesForTraining, function($a, $b) {
        return $a['PerformanceDiff'] <=> $b['PerformanceDiff']; // Sort in ascending order of performance difference
    });


    if (isset($_GET['view_year'])) 
    {
        $targetYear = intval($_GET['view_year']);
    }
    else 
    {
        $targetYear = date("Y"); // Set current year as default if no year is selected
    }

    $companyTaskCompletionOverYear = GetCompanyTaskCompletionOverYear($targetYear);
    $expectedTaskCompletionOverYear = $companyTaskCompletionOverYear['expectedTasksCompletion'];
    $actualTaskCompletionOverYear = $companyTaskCompletionOverYear['actualTasksCompletion'];


}





if ($targetProjectID) 
{

    //Emnpoyees for training?

    $projectTasks = GetProjectTasks($targetProjectID);

    // ADD THIS SAFETY CHECK RIGHT HERE
    if (!is_array($projectTasks)) {
        $projectTasks = [];
    }

    $projectTaskStats = GetProjectTaskStats($targetProjectID);

    $projectNotCompletedTasks = $projectTaskStats['NotCompleteTasks'];
    $projectCompletedTasks = $projectTaskStats['CompleteTasks'];
    $projectOverdueTasks = $projectTaskStats['OverdueTasks'];
    $projectTotalTasks = $projectNotCompletedTasks + $projectCompletedTasks + $projectOverdueTasks;

    $projectTaskCompletionOverMonths = GetProjectTaskCompletionOverMonths($targetProjectID);

    $projectMembers = GetProjectMembers($targetProjectID);

    if (!is_array($projectMembers)) {
        $projectMembers = [];
    }

    $projectOverseeingTeam = array_filter($projectMembers, function($member) {
        return $member['UserType'] == 'Manager' || $member['Leader'] == '1';
    });

    $projectWorkingTeam = array_filter($projectMembers, function($member) {
        return $member['UserType'] == 'Worker' && $member['Leader'] == '0';
    });


   

}



if ($targetUserID) 
{
    $userProjects = GetUserProjects($targetUserID);

    if (!is_array($userProjects)) {
        $userProjects = [];
    }

    //show user task stats for all projects, for the gauge
    $userProjectTaskStats = GetUserProjectTaskStats($targetUserID, null); //show user weighted task completion against average task completion of all employees in the company GetWeightedUserTaskCompletion($_SESSION["userID"]); //show overdue tasks for user
    //shwo user weighted task completion against average task completion of all employees in the company

    $userNotCompletedTasks = $userProjectTaskStats['NotCompleteTasks'];
    $userCompletedTasks = $userProjectTaskStats['CompleteTasks'];
    $userOverdueTasks = $userProjectTaskStats['OverdueTasks'];
    $userTotalTasks = $userNotCompletedTasks + $userCompletedTasks + $userOverdueTasks;

    //error_log("User Task Stats: Not Completed: $userNotCompletedTasks, Completed: $userCompletedTasks, Overdue: $userOverdueTasks, Total: $userTotalTasks");
    
    $userProjectTaskCompletionDiff = 0;
    $userProjectTaskCompletion = 0;

    if ($userTotalTasks > 0)
    {
        $userProjectTaskCompletion = GetWeightedUserTaskCompletion($targetUserID, null);
    
        if ($employeeTaskCompletionStandardDeviation > 0) 
        {
            $userProjectTaskCompletionDiff = ($userProjectTaskCompletion - $employeeTaskCompletionAverage) / $employeeTaskCompletionStandardDeviation; // Calculate how many standard deviations the user's completion is from the average
        }
    }

    //Get overdue tasks

    $userProjectTasks = GetUserProjectTasks($targetUserID, null);
    if (!is_array($userProjectTasks)) {$userProjectTasks = [];}
    $userOverdueProjectTasks = array_filter($userProjectTasks, function($task) { return $task['Status'] === 'Overdue'; });
}




// Pull Task Data 
$userPersonalTasks = GetUserPersonalTasks($_SESSION['userID']);

}
?>

<?php include "header.php"; ?>

<!-- If required login panel -->
<?php if ($_SESSION['userID'] == -1): ?>
<!-- Dashboard: Login Panel -->
<section id="login" class="panel mx-auto p-3 secondary-text">

    <img src="static/banner.png" alt="Make-It-All Banner" class="img-fluid d-block mx-auto mb-3" style="width:60%; max-width:95%;">

    <form id="login-form" class="d-flex flex-column mx-auto w-50">

        <div id="email" class="mb-3">
            <label for="inp-email"><h5>Email address</h5></label>
            <input type="email" class="form-control" id="inp-email" aria-describedby="email-help">
            <div id="email-help" class="form-text">Use your company email.</div>
        </div>

        <section>
            
            <div id="password" class="mb-3">
                <label for="inp-password"><h5>Password</h5></label>
                <input id="inp-password" type="password" class="form-control" id="inp-password">
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" onclick="showPassword()" class="form-check-input" id="show-password">
                <label class="form-check-label" for="show-password">Show Password</label>
            </div>
        </section>

        <button type="button" class="btn btn-primary mb-3 mx-auto" style="width: 200px;" onclick="handleLogin()">Login</button>
        
        <div id="activation-form" class="hidden">
            <div id="forename" class="mb-3">
                <label for="inp-forename"><h5>Forename</h5></label>
                <input type="forename" class="form-control" id="inp-forename">
            </div>

            <div id="surname" class="mb-3">
                <label for="inp-surname"><h5>Surname</h5></label>
                <input type="surname" class="form-control" id="inp-surname">
            </div>

            <div id="activate-help" class="form-text text-center mb-3">Enter Your full name aswell as your company email and desired password to activate your account</div>
        </div>

        <button id="activate-btn" type="button" class="btn btn-secondary mx-auto" style="width: 200px;" onclick="extendActivation()">Activate Account</button>
    </form>

    <script>
        function handleLogin() {
        const email = document.getElementById('inp-email').value;
        const password = document.getElementById('inp-password').value;

        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('email', email);
        formData.append('password', password);

        fetch('login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
            window.location.href = 'dashboard.php';
            } else {
            alert('Login failed: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
        }

        function handleActivateAccount() {
        const email = document.getElementById('inp-email').value;
        const password = document.getElementById('inp-password').value;
        const forename = document.getElementById('inp-forename').value;
        const surname = document.getElementById('inp-surname').value;

        /* Temporarily removed for faster testing.
        if (!email.endsWith("@make-it-all.co.uk")) {
            alert('Email must be a valid company email address ending with @make-it-all.co.uk');
            return;
        }

        if (password.length < 12 || password.length > 255) {
            alert('Password must be at least 12 characters long.');
            return;
        }

        if (!/[A-Z]/.test(password)) {
            alert('Password must contain at least one uppercase letter.');
            return;
        }

        if (!/[a-z]/.test(password)) {
            alert('Password must contain at least one lowercase letter.');
            return;
        }

        if (!/[0-9]/.test(password)) {
            alert('Password must contain at least one number.');
            return;
        }
    
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            alert('Password must contain at least one special character.');
            return;
        }

        */

        const formData = new FormData();
        formData.append('action', 'register');
        formData.append('email', email);
        formData.append('password', password); //Added this for testing, change for double hashing| workaround
        formData.append('forename', forename);
        formData.append('surname', surname);
        

        fetch('create.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
            alert('Account activation request sent successfully!\nPlease contact a manager to accept your request.');
            } else {
            alert('Account activation failed: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
        }
    </script>

</section> 
<?php elseif ($pageType == "settings"): ?>

    <!-- Dashboard: User settings -->
    <section id="settings" class="titled panel mx-auto">
        <h5 class="panel-title p-3">
            Settings
        </h5>

        <div id="settings-container" class="d-flex flex-column align-content-center">
            <form id="update-user-details" class="w-50 d-flex flex-column  mx-auto">
                <?php
                    $user = GetUser($_SESSION['userID']);
                    $user = $user[0];
                ?>

                <div id="forename" class="mb-3">
                    <label for="inp-forename"><h5>Forename</h5></label>
                    <input type="forename" class="form-control" id="inp-forename" value="<?php echo $user['Forename']; ?>">
                </div>

                <div id="surname" class="mb-3">
                    <label for="inp-surname"><h5>Surname</h5></label>
                    <input type="surname" class="form-control" id="inp-surname" value="<?php echo $user['Surname']; ?>">
                </div>

                <div id="email" class="mb-3">
                    <label for="inp-email"><h5>Email address</h5></label>
                    <input type="email" class="form-control" id="inp-email" aria-describedby="email-help" value="<?php echo $user['Email']; ?>">
                    <div id="email-help" class="form-text">Use your company email.</div>
                </div>
            
                <div id="password" class="mb-3">
                    <label for="inp-password"><h5>Password</h5></label>
                    <input id="inp-password" type="password" class="form-control" id="inp-password">
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" onclick="showPassword()" class="form-check-input" id="show-password">
                    <label class="form-check-label" for="show-password">Show Password</label>
                </div>

                <div id="update-help" class="form-text text-center mb-3">Re-Set Password to confirm changes</div>
        

                <button id="update-user-btn" type="button" class="btn btn-secondary mx-auto" style="width: 200px;" onclick="UpdateUser(<?php echo $_SESSION['userID']; ?>)">Update Account</button>
            </form>

            <button class="btn btn-danger m-3 mx-auto" data-bs-toggle="modal" data-bs-target="#warning-modal">
                Delete User
            </button>
        </div>
    </section>

    <!-- Warning Modal -->
    <div class="modal fade" id="warning-modal" tabindex="-1" aria-labelledby="warning-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow curved-borders">
                <!-- Modal Body -->
                <div class="modal-body p-0">

                    <div class="warning-message curved-borders d-flex justify-content-center align-items-center flex-wrap p-3 text-center">
                                            
                        <div class="d-flex align-items-center justify-content-center mb-2 me-5">
                            <img src="static/warning.png" alt="!" class="img-fluid me-3" style="width: 35px;">
                            <h5 id="training" class="mb-1 text-nowrap">Are you sure you want to delete this!</h5>
                        </div>
                                            
                        <div class="w-75 d-flex justify-content-around gap-3">
                            <button class="btn btn-outline-danger text-nowrap notify-buttons">
                                No
                            </button>

                            <button onclick="DeleteUser(<?php echo $_SESSION['userID']; ?>)" class="btn btn-danger text-nowrap notify-buttons">
                                Yes
                            </button>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>

    <script>
        function DeleteUser(userID)
        {
            const formData = new FormData();
            formData.append('action', 'user');
            formData.append('userID', userID);

            fetch('delete.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User deleted successfully');
                    const formData2 = new FormData();
                    formData2.append('action', 'logout');
                    fetch('login.php', {
                        method: 'POST',
                        body: formData2});
                    location.reload();
                } else {
                    alert('Failed to delete user: ' + data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function UpdateUser(userID)
        {
            const forename = document.getElementById('inp-forename').value;
            const surname = document.getElementById('inp-surname').value;
            const email = document.getElementById('inp-email').value;
            const password = document.getElementById('inp-password').value;

            if (!password) {
                alert('Please enter your password to confirm changes.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update-user');
            formData.append('userID', userID);
            formData.append('forename', forename);
            formData.append('surname', surname);
            formData.append('email', email);
            formData.append('password', password);

            fetch('update.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Account updated successfully!');
                    location.reload();
                } else {
                    alert('Failed to update account: ' + data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>

<?php else: ?>

<!-- Dashboard: Redirect Panel -->
<section id="redir" class="titled panel mx-auto">
    <h5 class="panel-title p-3">
        Quick Access
    </h5>

    <div id="redir-holder" class="d-flex flex-wrap">
        
        <div id="redir-project-holder" class="col-lg-6 col-sm-12 p-3 d-flex flex-column align-items-center redirect-holder">

            <!-- Main Redirect -->
            <div id="project-main-redir" class="main-redir card">
                <div class="card-body d-flex flex-column justify-content-between ">
                    <h1 class="card-title primary-text ">My Projects</h1>
                    
                    <img src="static/projects-icon.png" class="redir-icon">
                    <h3 class="card-text redir-text"> View ongoing projects and manage tasks </h3>
                    
                    <a href="search.php?type=projects" class="btn btn-primary w-100">Search Projects</a>
                </div>
            </div>

            <!-- Recently Accessed Projects -->
            <section id="recent-project-holder" class="w-100 d-flex recent-redir-holder">
                
                <?php if (isset($project1Data)): ?>
                <div id="project-1-redir" class="card m-3 w-50">
                    <div class="card-body d-flex flex-column">
                        <h4 class="card-title text-nowrap overflow-ellipsis" title="<?php echo $project1Data['Title'] ?>">
                            <?php echo $project1Data["Title"] ?>
                        </h4>

                        <p class="card-text flex-grow-1">
                            <?php echo $project1Data["Description"] ?>
                        </p>
                        
                        <a href="display.php?page-type=project&id=<?php echo $project1ID ?>" class="btn btn-primary w-100 mt-2">View Project</a>
                    </div>
                </div>
            <?php endif; ?>

                <?php if (isset($project2Data)): ?>
                <div id="project-2-redir" class="card m-3 w-50 ">
                    <div class="card-body d-flex flex-column">
                        <h4 class="card-title text-nowrap overflow-ellipsis"><?php echo $project2Data["Title"] ?></h4>
                        
                        <p class="card-text flex-grow-1"><?php echo $project2Data["Description"] ?></p>
                        
                        <a href="display.php?page-type=project&id=<?php echo $project2ID ?>" class="btn btn-primary w-100">View Project</a>
                    </div>
                </div>
                <?php endif; ?>

            </section>

        </div>

        <div id="redir-knowledge-holder" class="col-lg-6 col-sm-12 p-3 d-flex flex-column align-items-center redirect-holder">

            <div id="knowledge-main-redir" class="main-redir card">
                <div class="card-body">
                    <h1 class="card-title primary-text">My Knowledge</h1>
                    
                    <img src="static/knowledge-icon.png" class="redir-icon">
                    <h3 class="card-text redir-text">Access resources and join discussions</h3>
                    
                    <a href="search.php?type=knowledge" class="btn btn-primary w-100">Search Knowledge</a>
                </div>
            </div>

            <!-- Recently Accessed Knowledge -->
            <section id="recent-knowledge-holder" class="w-100 fill-y d-flex align-items-center recent-redir-holder">
                <?php if (isset($topic1Data)): ?>
                <div id="knowledge-1-redir" class="card m-3 w-50">
                    <div class="card-body d-flex flex-column">
                        <h4 class="card-title flex-grow-1 overflow-ellipsis text-center"><?php echo $topic1Data["Title"]; ?></h4>
                        
                        <a href="display.php?page-type=topic&id=<?php echo $topic1ID; ?>" class="btn btn-primary w-100">View Knowledge</a>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (isset($topic2Data)): ?>
                <div id="knowledge-2-redir" class="card m-3 w-50">
                    <div class="card-body d-flex flex-column">
                        <h4 class="card-title flex-grow-1 overflow-ellipsis text-center"><?php echo $topic2Data["Title"] ?></h4>
                        
                        <a href="display.php?page-type=topic&id=<?php echo $topic2ID ?>" class="btn btn-primary w-100">View Knowledge</a>
                    </div>
                </div>
                <?php endif; ?>

            </section>

        </div>

    </div>

</section>

<!-- Dashboard: Metrics -->
<section id="metrics" class="titled panel mx-auto">
    <h5 class="panel-title p-3">
        Metrics
    </h5>

    <?php if ($_SESSION['userType'] === "Worker"): ?>

        <section class="nav-row-2 w-100 d-flex flex-wrap justify-content-between nav-buttons">
            <div class="btn nav-btn flex-grow-1 text-center" id="btn-general" style="background-color: var(--primary-btn);">General</div>
        </section>

        <!-- Employee General -->
        <section id="employee-metrics" class="p-3">

            <!-- Metrics top row: Project Overview & Task Stats -->
            <div id="employee-general-top-row" class="d-flex flex-wrap">

                <div id="users-projects" class="p-3 d-flex flex-column col-lg-6 col-md-5 col-sm-12">

                    <h4 class="metric-sub-section metric-sub-section-title mt-2 p-2">Your Projects (<span id="user-project-count"><?php echo count($userProjects); ?></span>)</h4>
                    <!-- this parent div is to make scrollbar fit nicer inside container -->
                    <div class="metric-sub-section h-100 p-3 gap-2 d-flex flex-column">
                        <div id="user-project-container" class="metric-sub-section custom-scrollbar px-3 py-1 gap-2 d-flex flex-column">

                            <?php foreach ($userProjects as $index => $project) : ?>
                                
                                <?php
                                $total = intval($project['TotalTasks']);
                                $complete = intval($project['TasksComplete']);

                                if ($total > 0) {
                                    $projectCompletion = round(($complete / $total) * 100);
                                } else {
                                    $projectCompletion = 0;
                                }

                                if ($projectCompletion > 50) {
                                    $inner = "";
                                    $outer = "hidden";
                                } else {
                                    $inner = "hidden";
                                    $outer = "";
                                }
                            
                                ?>
                                
                                <div id="project-<?php echo $project['ProjectID']; ?>" class="bg-white curved-borders p-2">
                                    <h6 id="project-title" class="text-wrap"><?php echo $project['Title']; ?></h6>
                                    <div id="project-progress-bar" class="progress m-1 d-flex" role="progressbar" aria-valuenow="<?php echo $projectCompletion; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <div id="project-progress" class="progress-bar" style="width: <?php echo $projectCompletion; ?>%; background-color: var(--not-complete);"><span class="<?php echo $inner; ?>"><?php echo $projectCompletion; ?>% Complete</span></div>
                                        <span id="progress-bar-value" class="<?php echo $outer; ?>">ㅤ<?php echo $projectCompletion; ?>% Complete</span>
                                    </div>
                                </div> 

                            <?php endforeach; ?>
                        
                        </div>
                    </div>

                </div>

                <div id="task-metrics" class="p-3 col-lg-6 col-md-7 col-sm-12">
                    
                    <?php if ($userTotalTasks > 0): ?>  

                        <h4 class="p-2 mb-2 bold">Task Metrics</h4>
                        <div class="d-flex justify-content-around">
                            
                            <div id="average-task-completed-container" class="metric-sub-section curved-borders col-7 p-3">
                                <div class="metric-inner-sub-section curved-borders h-100 p-2 pt-3 pb-4 d-flex flex-column justify-content-between">
                                    <?php
                                        if ($userProjectTaskCompletionDiff == 0) 
                                        {
                                            $textWord = "Average";
                                            $textClass = "black-text";
                                            $text1 = "You are exactly average.";
                                            $text2  = "You completed the same number of tasks as the company average.";
                                        } 
                                        else 
                                        {
                                            $isNegative = ($userProjectTaskCompletionDiff < 0);
                                            $textWord   = $isNegative ? "Less" : "More";
                                            $textClass  = $isNegative ? "red-text" : "green-text";
                                            $text1 = "You completed ";
                                            $text2 =  " tasks than the <br> average employee.";
                                        }
                                    ?>
                                
                                    <h4><?php echo $text1; ?></h4>

                                    <h5>
                                        <span id="task-variance"><?php echo abs(round($userProjectTaskCompletionDiff, 2)); ?></span><br>
                                        <span class="<?php echo $textClass; ?>"><?php echo $textWord; ?></span> 
                                        <?php echo $text2; ?>
                                    </h5>

                                    <div class="task-scale">
                                        <div class="scale-bar">
                                            <div class="segment scale-red"></div>
                                            <div class="segment scale-orange"></div>
                                            <div class="segment scale-yellow"></div>
                                            <div class="segment scale-light-green"></div>
                                            <div class="segment scale-green"></div>
                                            <div id="task-scale-needle" style="left: clamp(0%, <?php echo max(0, min(100, ($userProjectTaskCompletionDiff + 3) / 6 * 100)); ?>%, 100%);"></div>
                                        </div>

                                        <div class="scale-labels">
                                            <span class="scale-extreme">3</span>
                                            <span class="scale-mid">2</span>
                                            <span class="scale-low">1</span>
                                            <span>0</span>
                                            <span class="scale-low">1</span>
                                            <span class="scale-mid">2</span>
                                            <span class="scale-extreme">3</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="h-100 col-4 metric-sub-section curved-borders p-2">
                                <div class="metric-inner-sub-section gauge-container">
                                    <canvas id="employee-general-task-gauge" class="task-gauge"></canvas>
                                    <script>
                                        const notCompleted = <?php echo $userNotCompletedTasks; ?>;
                                        const completed = <?php echo $userCompletedTasks; ?>;
                                        const overdue = <?php echo $userOverdueTasks; ?>;

                                        new Chart(document.getElementById("employee-general-task-gauge"), {
                                            type: "doughnut",
                                            data: {
                                                datasets: [{
                                                    data: [completed, overdue, notCompleted],
                                                    backgroundColor: ["#007BFF", "#C82333", "#d9d9d9"],
                                                    borderWidth: 0
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                aspectRatio: 1,
                                                cutoutPercentage: 88,
                                                circumference: 1.3 * Math.PI,
                                                rotation: 0.85 * Math.PI,
                                                legend: { display: false },
                                                tooltips: { enabled: false },
                                                layout: {
                                                    padding:0
                                                }
                                            }
                                        });
                                    </script>
                                    <div class="gauge-text-overlay">
                                        <h1 id="user-task-count" class="mt-3">
                                            <span id="users-completed-task-count"><?php echo $userCompletedTasks; ?></span>/<span id="users-total-task-count"><?php echo $userTotalTasks; ?></span>
                                        </h1>
                                        <h2 id="tasks-completed">Tasks<br>Completed</h2>
                                    </div>
                                    
                                </div>
                                <h4 class="red-pop-out-text">Expected Tasks: <span id="user-expected-task-count"><?php echo $userCompletedTasks + $userOverdueTasks; ?></span></h4>
                                
                            </div>

                            

                        </div>
                        <?php 
                        if ($userProjectTaskCompletionDiff < -1) :// If user is more than 1 standard deviation below average, suggest training { ?>
                        <h2 id="training" class ="red-pop-out-text p-1 mx-3">This Employee may need training!</h2>
                        <?php endif; ?>

                    <?php elseif ($userTotalTasks == 0) : ?>
                        <h2 id="no-tasks" class ="red-pop-out-text p-1 mx-3">No Tasks Assigned</h2>
                    <?php endif; ?>
                </div>

                
            </div>

            <br>
            <br>

            <!-- Metrics bottom row: Overdue Tasks -->
            <?php if ($userOverdueTasks > 0): ?>
                <div id="overdue-tasks" class="p-3">
                    <div class="metric-sub-section metric-sub-section-title p-2">
                        <h4>Overdue Tasks (<span id="user-overdue-tasks"><?php echo ($userOverdueTasks); ?></span>)</h4>
                    </div>
                    <div class="overdue-tasks metric-sub-section p-3 gap-2 d-flex flex-column">
                        <div id="overdue-tasks-container" class="metric-sub-section custom-scrollbar px-3 py-1 gap-2 d-flex flex-column">
                            <?php foreach($userOverdueProjectTasks as $task): ?>
                                <div id="task-<?php echo $task['TaskID']; ?>" class="task card w-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h3 id="task-title" class="card-title m-0"><?php echo $task['Title']; ?></h3>
                                        
                                        <div class="d-flex">
                                            <?php 
                                            if ($task['Priority'] == 1)
                                            {
                                                echo "<span id=\"task-priority\" class=\"badge m-2 text-bg-info\">Low Priority</span>";
                                            }
                                            else if ($task['Priority'] == 2)
                                            {
                                                echo "<span id=\"task-priority\" class=\"badge m-2 text-bg-warning\">Medium Priority</span>";
                                            }
                                            else if ($task['Priority'] == 3)
                                            {
                                                echo "<span id=\"task-priority\" class=\"badge m-2 text-bg-danger\">High Priority</span>";
                                            }
                                            ?>

                                            <button id="delete-task" class="btn btn-danger" onclick="DeleteTask(<?php echo $task['TaskID']; ?>)">🗑️</button>
                                        </div>
                                    </div>

                                    <div id="task-description" class="card-body">
                                        <?php echo $task['Description']; ?>
                                    </div>

                                    <div class="card-footer d-flex justify-content-between align-items-center">
                                        <!-- Leave element but clear inner html. this will ensure deadline still gets pushed to the end. -->
                                        
                                        <div class="d-flex align-items-center">
                                            <span id="deadline">Deadline: <strong><?php echo $task['Deadline']; ?></strong></span>
                                            <button 
                                                id="status" 
                                                class="btn btn-primary p-2 m-2 <?php echo strtolower(str_replace(' ', '-', $task['Status'])); ?>-btn contrast-text" 
                                                onclick="UpdateStatus(<?php echo $task['TaskID']; ?>)"><?php echo $task['Status']; ?>
                                            </button>
                                        </div>
                                        
                                    </div>
                                </div>

                            <?php endforeach; ?>     
                                        
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

    <?php endif; ?> 
    
    
    <?php if ($_SESSION['userType'] === "Manager"): ?> <!-- Manager Metrics -->
    
        <section class="nav-row-2 w-100 d-flex flex-wrap justify-content-between nav-buttons">
            <a class="man-nav-btn btn nav-btn flex-grow-1 text-center nav-left nav-active" id="btn-general" onclick="managerMetricsNav(this.id)">General</a>
            <a class="man-nav-btn btn nav-btn flex-grow-1 text-center" id="btn-project" onclick="managerMetricsNav(this.id)">Project Management</a>
            <a class="man-nav-btn btn nav-btn flex-grow-1 text-center nav-right" id="btn-employee" onclick="managerMetricsNav(this.id)">Employee Overview</a>
        </section>

        <section id="manager-metrics" class="p-3">

            <!-- Manager General -->
            <!-- Metrics top row: Project Overview & Task Stats -->
            <div id="man-general" class="flex-wrap justify-content-between p-5">

                <div id="gauges" class="gauges-manager-general p-3 d-flex flex-column col-lg-3 col-md-3 col-sm-12">
                    <div class="d-flex flex-wrap justify-content-center mt-2 me-0 gap-4">
                        <div class="metric-sub-section curved-borders p-2 col-lg-10 col-md-10 col-sm-5">
                            <h4 class="p-2 mb-2 fw-bold text-center">Task This Month</h4>
                            <div class="metric-inner-sub-section gauge-container">
                                <canvas id="manager-general-task-gauge" class="task-gauge"></canvas>
                                <script>

                                    const generalNotCompleted = <?php echo $allNotCompletedTasks;?>;
                                    const generalCompleted = <?php echo $allCompletedTasks; ?>;
                                    const generalOverdue = <?php echo $allOverdueTasks; ?>;
                                    const generalTotal = <?php echo $allTotalTasks; ?>;
                                    //Different values for different gauges.

                                    new Chart(document.getElementById("manager-general-task-gauge"), {
                                        type: "doughnut",
                                        data: {
                                            datasets: [{
                                                data: [generalCompleted, generalOverdue, generalNotCompleted],
                                                backgroundColor: ["#007BFF", "#C82333", "#d9d9d9"],
                                                borderWidth: 0
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            aspectRatio: 1,
                                            cutoutPercentage: 88,
                                            circumference: 1.3 * Math.PI,
                                            rotation: 0.85 * Math.PI,
                                            legend: { display: false },
                                            tooltips: { enabled: false },
                                            layout: {
                                                padding: 0
                                            }
                                        }
                                    });
                                </script>
                                <div class="gauge-text-overlay">
                                    <h1 id="user-task-count" class="mt-3">
                                        <span id="users-completed-task-count"><?php echo $allCompletedTasks; ?></span>/<span id="users-total-task-count"><?php echo $allTotalTasks; ?></span>
                                    </h1>
                                    <h2 id="tasks-completed">Tasks<br>Completed</h2>
                                </div>

                            </div>
                            <h4 class="red-pop-out-text">Expected Tasks: <span id="user-expected-task-count"><?php echo $allCompletedTasks + $allOverdueTasks; ?></span></h4>

                        </div>

                        <div id="company-statistics" class="metric-sub-section curved-borders col-lg-11 col-md-11 col-sm-5 p-3">
                            <div class="metric-inner-sub-section curved-borders h-100 p-2 pt-3 pb-4 d-flex flex-column justify-content-between">
                                <div class="metric-segment">
                                    <h4>Employee Count:</h4>
                                    <span id="employee-count" class="metric-value"><?php echo $employeeCount; ?></span>
                                </div>

                                <hr class="metric-divider">

                                <div class="metric-segment">
                                    <h4 class="metric-label">Active Projects:</h4>
                                    <span id="active-projects" class="metric-value"><?php echo $activeProjects; ?></span>
                                </div>

                                <hr class="metric-divider">

                                <div class="metric-segment">
                                    <h4 class="metric-label">Knowledge Posts:</h4>
                                    <span id="knowledge-posts" class="metric-value"><?php echo $postCount; ?></span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div id="met-graph" class="col-lg-9 col-md-9 col-sm-12 d-flex flex-column justify-content-around">
                    <div id="graph" class="metric-sub-section curved-borders d-flex flex-column align-items-center mb-4 py-2" >
                            <?php if ($targetYear) : ?>
                            <div class="fw-bold graph-title top-curved text-center mt-4 py-3 px-5 mb-0 d-flex flex-wrap justify-content-between">
                                <h4 >Task Completion in <?php echo $targetYear; ?></h4>
                                <select name="year" id="year-select" style="width: 150px;" onchange="window.location.href='?view_year=' + this.value + '#general-graph';">
                                    <option selected="" disabled="">Select Year</option>

                                        <?php 
                                            $currentYear = date('Y');
                                            for ($year = 2025; $year <= $currentYear; $year++) : ?> <!-- Assume comapny started in 2025-->
                                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                        <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="graph-container graph-general bottom-curved mx-4 mb-4 mt-0 px-5 py-3">
                            
                            
                            <canvas id="general-graph"></canvas>

                            <script>

                                const allMonths = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                                let expectedData = <?php echo json_encode(array_values($expectedTaskCompletionOverYear)); ?>;
                                let completedData = <?php echo json_encode(array_values($actualTaskCompletionOverYear)); ?>;
                                
                                let allCurrentMonth = new Date().getMonth(); //getMonth is 0 indexed, add 1 to match with month numbers in data

                                for (let i = 1; i < expectedData.length; i++) {
                                    expectedData[i] += expectedData[i - 1];
                                }

                                for (let i = 1; i < completedData.length; i++) {
                                    if (i > allCurrentMonth) { //allCurrentMonth is 1-indexed, so subtract 1 to match array indices
                                        completedData[i] = null;
                                    }
                                    else
                                    {
                                        completedData[i] += completedData[i - 1];
                                    }
                                }



                                const barColors = completedData.map((val, i) => {
                                    if (val > expectedData[i]) return "#28a745";//green
                                    if (val < expectedData[i]) return "#C82333";//red
                                    return "#007BFF";//blue
                                });

                                var generalCtx = document.getElementById('general-graph').getContext('2d');
                                var taskChart = new Chart(generalCtx, {
                                    type: 'bar',
                                    data: {
                                        labels: allMonths,
                                        datasets: [
                                            {
                                                label: 'Expected Tasks',
                                                type: 'line',
                                                data: expectedData,
                                                fill: false,
                                                borderColor: '#333',
                                                borderDash: [5, 5],
                                                pointStyle: 'rectRot',
                                                lineTension: 0.3
                                            },
                                            {
                                                label: 'Tasks Completed',
                                                data: completedData,
                                                backgroundColor: barColors,
                                                borderWidth: 1
                                            }
                                        ]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        legend: {
                                            display: true,
                                            position: 'bottom',
                                            labels: {
                                                usePointStyle: true,
                                                padding: 20,
                                                fontStyle: 'bold'
                                            }
                                        },
                                        scales: {
                                            yAxes: [{
                                                ticks: { beginAtZero: true, stepSize: 2 }
                                            }],
                                            xAxes: [{
                                                gridLines: { display: false }
                                            }]
                                        }
                                    }
                                });
                            </script>

                        <?php else: ?>
                            <h4 class="fw-bold text-center">Select a year to view task completion data.</h4>
                        <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($allOverdueTasks > 0): ?>
                        <div id="overdue-tasks mt-1">
                            <div class="metric-sub-section metric-sub-section-title p-2">
                                <h4>Overdue Tasks (<span id="user-overdue-tasks"><?php echo ($allOverdueTasks); ?></span>)</h4>
                            </div>
                            <div class="overdue-manager-general metric-sub-section p-3 gap-2 d-flex flex-column">
                                <div id="overdue-tasks-container" class="task-container metric-sub-section custom-scrollbar px-3 py-1 gap-2 d-flex flex-column fill-y">
                                    <?php foreach($allOverdueProjectTasks as $task): ?>

                                        <div id="task-<?php echo $task['TaskID']; ?>" class="task card w-100">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h3 id="task-title" class="card-title m-0"><?php echo $task['Title']; ?></h3>
                                                
                                                <div class="d-flex">
                                                    <?php 
                                                    if ($task['Priority'] == 1)
                                                    {
                                                        echo "<span id=\"task-priority\" class=\"badge m-2 text-bg-info\">Low Priority</span>";
                                                    }
                                                    else if ($task['Priority'] == 2)
                                                    {
                                                        echo "<span id=\"task-priority\" class=\"badge m-2 text-bg-warning\">Medium Priority</span>";
                                                    }
                                                    else if ($task['Priority'] == 3)
                                                    {
                                                        echo "<span id=\"task-priority\" class=\"badge m-2 text-bg-danger\">High Priority</span>";
                                                    }
                                                    ?>

                                                    <button id="delete-task" class="btn btn-danger" onclick="DeleteTask(<?php echo $task['TaskID']; ?>)">🗑️</button>
                                                </div>
                                            </div>

                                            <div id="task-description" class="card-body">
                                                <?php echo $task['Description']; ?>
                                            </div>

                                            <div class="card-footer d-flex justify-content-between align-items-center">
                                                <!-- Leave element but clear inner html. this will ensure deadline still gets pushed to the end. -->
                                                
                                                <div class="d-flex align-items-center">
                                                    <span id="deadline">Deadline: <strong><?php echo $task['Deadline']; ?></strong></span>
                                                    <button 
                                                        id="status" 
                                                        class="btn btn-primary p-2 m-2 <?php echo strtolower(str_replace(' ', '-', $task['Status'])); ?>-btn contrast-text" 
                                                        onclick="UpdateStatus(<?php echo $task['TaskID']; ?>)"><?php echo $task['Status']; ?>
                                                    </button>
                                                </div>
                                                
                                            </div>
                                        </div>

                                    <?php endforeach; ?>     
                                                
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="employees-for-training" class="p-4 pe-0 w-100">

                    <h4 class="metric-sub-section metric-sub-section-title mt-2 p-2">Employees for Training (<span id="employees-for-training-count"><?php echo count($employeesForTraining); ?></span>)</h4>
                    <!-- this parent div is to make scrollbar fit nicer inside container -->
                    <div class="metric-sub-section h-100 p-3 gap-2 d-flex flex-column w-100">
                        <?php if (count($employeesForTraining) > 0) : ?>
                            <div id="employees-for-training-container" class="metric-sub-section custom-scrollbar px-3 py-1 gap-2 d-flex flex-wrap justify-content-around">
                            <?php foreach ($employeesForTraining as $employee) : ?>   
                                    <div class="panel p-4 d-flex justify-content-between flex-column col-lg-5 col-md-12 col-sm-12">
                                            <div class="d-flex flex-column justify-content-between">
                                                <span class="content-box p-2 px-4 m-0 gap-2"><?php echo $employee['Forename'] . " " . $employee['Surname'] ?><span class="badge bg-non-technical ms-3">Team Leader</span></span>
                                                <div>
                                                    <div class="h-100 p-2 pt-3 pb-4 d-flex flex-column justify-content-between align-items-center">
                                                        <h4 class="text-center">Productivity vs. Average</h4>
                                                        <h5>
                                                            <span id="task-variance"><?php echo round($employee['PerformanceDiff'], 2); ?></span><br>
                                                        </h5>

                                                        <div class="task-scale">
                                                        <div class="scale-bar">
                                                            <div class="segment scale-red"></div>
                                                            <div class="segment scale-orange"></div>
                                                            <div class="segment scale-yellow"></div>
                                                            <div class="segment scale-light-green"></div>
                                                            <div class="segment scale-green"></div>
                                                            <div id="task-scale-needle" style="left: clamp(0%, <?php echo max(0, min(100, ($employee['PerformanceDiff'] + 3) / 6 * 100)); ?>%, 100%);"></div>
                                                        </div>

                                                        <div class="scale-labels">
                                                            <span class="scale-extreme">3</span>
                                                            <span class="scale-mid">2</span>
                                                            <span class="scale-low">1</span>
                                                            <span>0</span>
                                                            <span class="scale-low">1</span>
                                                            <span class="scale-mid">2</span>
                                                            <span class="scale-extreme">3</span>
                                                        </div>
                                                    </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex flex-wrap justify-content-center">
                                                <button class="w-100 btn btn-primary text-nowrap mb-2" onclick="window.location.href='?view_employee=<?php echo $employee['UserID']; ?>' + '#employee-metrics';">
                                                    View Employee's Page
                                                </button>
                                            </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div id="man-project" class="hidden">
                <select name="project" id="project" style="width: 150px;" onchange="window.location.href='?view_project=' + this.value + '#manager-project';">
                    <option selected="" disabled="">Select Project</option>
                    <?php foreach ($allProjects as $project) : ?>
                        <option value="<?php echo $project['ProjectID']; ?>"><?php echo $project['Title']; ?></option>
                    <?php endforeach; ?>
                    
                </select>
                <?php if (isset($_GET['view_project'])): ?>
                    <div id="current-project" class="titled panel">
                        <h2 class="panel-title"><?php echo $allProjects[$_GET['view_project']]['Title']; ?></h2>
                        <div id="project-metric-overview-container" class="d-flex justify-content-between flex-column p-5">
                            <div id="project-metric-overview" class="metric-inner-sub-section w-100 curved-borders d-flex p-3 gap-2 d-flex justify-content-between flex-row">
                                <div class="metric-sub-section curved-borders p-2 col-2">
                                    <h4 class="p-2 mb-2 fw-bold text-center">Project Tasks</h4>

                                    <div class="metric-inner-sub-section gauge-container">
                                        <canvas id="manager-project-task-gauge" class="task-gauge"></canvas>
                                        <script>
                                            const projectNotCompleted = <?php echo $projectNotCompletedTasks; ?>;
                                            const projectCompleted = <?php echo $projectCompletedTasks; ?>;
                                            const projectOverdue = <?php echo $projectOverdueTasks; ?>;

                                            new Chart(document.getElementById("manager-project-task-gauge"), {
                                                type: "doughnut",
                                                data: { 
                                                    datasets: [{
                                                        data: [projectCompleted, projectOverdue, projectNotCompleted],
                                                        backgroundColor: ["#007BFF", "#C82333", "#d9d9d9"],
                                                        borderWidth: 0
                                                    }]
                                                },

                                                options: {
                                                    responsive: true,
                                                    aspectRatio: 1,
                                                    cutoutPercentage: 88,
                                                    circumference: 1.3 * Math.PI,
                                                    rotation: 0.85 * Math.PI,
                                                    legend: { display: false },
                                                    tooltips: { enabled: false },
                                                    layout: {
                                                        padding: 0
                                                    }
                                                }
                                            });
                                        </script>
                                        <div class="gauge-text-overlay">
                                            <h1 id="user-task-count" class="mt-3">
                                                <span id="project-completed-task-count"><?php echo $projectCompletedTasks; ?></span>/<span id="project-total-task-count"><?php echo $projectTotalTasks; ?></span>
                                            </h1>
                                            <h2 id="tasks-completed">Tasks<br>Completed</h2>
                                        </div>

                                    </div>
                                    <h4 class="red-pop-out-text">Expected Tasks: <span id="user-expected-task-count"><?php echo $projectCompletedTasks + $projectOverdueTasks; ?></span></h4>

                                </div>

                                <div id="users-containers" class="curved-borders panel col-9 fill-y d-flex flex-wrap p-3">
                                    <h4 class="fw-bold w-100">Overseeing Team (<span id="uppers"><?php echo count($projectOverseeingTeam); ?></span>)</h4>

                                    <?php foreach ($projectOverseeingTeam as $member) : ?>  
                                        <a href="?view_employee=<?php echo $member['UserID']; ?>#employee-metrics" class="content-box p-2 px-4 mx-3 gap-2">
                                            <?php echo $member['Forename'] . " " . $member['Surname']; ?> 
                                            <span class="badge bg-non-technical ms-3"><?php echo $member['UserType'] == 'Manager' ? 'Manager' : 'Team Leader'; ?></span>
                                        </a>
                                    <?php endforeach; ?>

                                    <span class="seperator"></span>

                                    <h4 class="fw-bold w-100">Project Team (<span id="lowers"><?php echo count($projectWorkingTeam); ?></span>)</h4>

                                    <?php foreach ($projectWorkingTeam as $member) : ?>
                                        <a href="?view_employee=<?php echo $member['UserID']; ?>#employee-metrics" class="content-box p-2 px-4 mx-3 gap-2">
                                            <?php echo $member['Forename'] . " " . $member['Surname']; ?>
                                        </a>
                                    <?php endforeach; ?>

                                </div>
                            </div>
                            <br>
                            <div id="met-graph" class="metric-sub-section curved-borders d-flex flex-column align-items-center justify-content-center p-1 m-3 w-100">

                                <h4 class="fw-bold graph-title top-curved text-center mt-4 py-3 px-5 mb-0">Project Timeline</h4>
                                <div class="graph-container bottom-curved mx-4 mb-4 mt-0 px-5 py-3">
                                    <canvas id="project-graph"></canvas>
                                    <script>
                                        // This works but is veyr messy and wokrs better for data smapoes wiht a lot of months need to sort this out for shorter time frames and clean up code.
                                        expectedTasks = <?php echo json_encode($projectTaskCompletionOverMonths['expectedTasksCompletion']); ?>;
                                        actualTasks = <?php echo json_encode($projectTaskCompletionOverMonths['actualTasksCompletion']); ?>;

                                        deadlineMonth = "<?php echo date('Y-m', strtotime($allProjects[$targetProjectID]['Deadline'])); ?>";

                                        const projectMonths = Object.keys(expectedTasks);
                                        const projectCurrentMonth = new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0');

                                        let projectExpectedValues = Object.values(expectedTasks);
                                        let projectActualValues = Object.values(actualTasks);

                                        for (let i = 1; i < projectExpectedValues.length; i++) {
                                            projectExpectedValues[i] += projectExpectedValues[i - 1];
                                        }

                                        for (let i = 1; i < projectActualValues.length; i++) {
                                            if (projectMonths[i] > projectCurrentMonth) {
                                                projectActualValues[i] = null;
                                            }
                                            else
                                            {
                                                projectActualValues[i] += projectActualValues[i - 1] ?? 0; //tbf this is not need as once soemthing is null, the rest are too
                                            }
                                        }


                                        const projectBarColors = projectActualValues.map((val, i) => {
                                            if (val > projectExpectedValues[i]) return "#28a745";//green
                                            if (val < projectExpectedValues[i]) return "#C82333";//red
                                            return "#007BFF";//blue
                                        });




                                        var projectCtx = document.getElementById('project-graph').getContext('2d');
                                        var taskChart = new Chart(projectCtx, {
                                            type: 'bar',
                                            data: {
                                                labels: projectMonths,
                                                datasets: [
                                                    {
                                                        label: 'Expected Tasks',
                                                        type: 'line',
                                                        data: projectExpectedValues,
                                                        fill: false,
                                                        borderColor: '#333',
                                                        borderDash: [5, 5],
                                                        pointStyle: 'rectRot',
                                                        lineTension: 0.3
                                                    },
                                                    {
                                                        label: 'Tasks Completed',
                                                        data: projectActualValues,
                                                        backgroundColor: projectBarColors,
                                                        borderWidth: 1
                                                    }
                                                ]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                annotation: {
                                                    drawTime: 'afterDatasetsDraw',
                                                    annotations: [{
                                                        type: 'line',
                                                        mode: 'vertical',
                                                        scaleID: 'x-axis-0',
                                                        value: deadlineMonth, // Snap to the deadline month string
                                                        borderColor: 'red',
                                                        borderWidth: 2,
                                                        borderDash: [10, 5],
                                                        label: {
                                                            enabled: true,
                                                            content: 'Deadline',
                                                            position: 'bottom',
                                                            xAdjust: 0,
                                                            yAdjust: 10
                                                        }
                                                    }]
                                                },
                                                legend: {
                                                    display: true,
                                                    position: 'bottom',
                                                    labels: {
                                                        usePointStyle: true,
                                                        padding: 20,
                                                        fontStyle: 'bold'
                                                    }
                                                },
                                                scales: {
                                                    yAxes: [{
                                                        ticks: { beginAtZero: true, stepSize: 2 }
                                                    }],
                                                    xAxes: [{
                                                        id: 'x-axis-0',
                                                        gridLines: { display: false }
                                                    }]
                                                }
                                            }
                                        });
                                    </script>
                                </div>
                            </div>

                            <div id="project-metric-redirect" class="d-flex justify-content-around">
                                <a href="display.php?page-type=project&id=<?php echo $targetProjectID; ?>" class="btn btn-primary text-nowrap m-1">
                                    View Project Page
                                </a>

                                <a href="search.php?type=projects" class="btn btn-primary text-nowrap m-1">
                                    View All Projects
                                </a>
                            </div>

                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column justify-content-center align-items-center gap-3 p-5">
                        <h2 class="red-pop-out-text">Select a project to view their metrics.</h2>
                    </div>
                <?php endif; ?>
            </div>

            <div id="man-employee" class="hidden">
                <select name="employee" id="employeeSelector" style="width: 180px;" onchange="window.location.href='?view_employee=' + this.value + '#employee-metrics';">

                    <option selected="" disabled="">Select Employee</option>
                    <?php foreach ($allWorkers as $employee) : ?>
                        <option value="<?php echo $employee['UserID']; ?>"><?php echo $employee['Forename'] . " " . $employee['Surname']; ?></option>
                    <?php endforeach; ?>
                </select>

                <?php if (isset($_GET['view_employee'])): ?>
                    <div class="mt-2">
                        <h5 class="mb-2">Viewing Employee: <?php echo $allWorkers[$_GET['view_employee']]['Forename'] . " " . $allWorkers[$_GET['view_employee']]['Surname']; ?></h5>
                    </div>
                    
                    <section id="employee-metrics" class="p-3">

                        <!-- Metrics top row: Project Overview & Task Stats -->
                        <div id="manager-employee-top-row" class="d-flex flex-wrap">

                            <div id="users-projects" class="p-3 d-flex flex-column col-lg-6 col-md-5 col-sm-12">

                                <h4 class="metric-sub-section metric-sub-section-title mt-2 p-2">Your Projects (<span id="user-project-count"><?php echo count($userProjects); ?></span>)</h4>
                                <!-- this parent div is to make scrollbar fit nicer inside container -->
                                <div class="metric-sub-section h-100 p-3 gap-2 d-flex flex-column">
                                    <div id="user-project-container" class="metric-sub-section custom-scrollbar px-3 py-1 gap-2 d-flex flex-column">

                                        <?php foreach ($userProjects as $index => $project) : ?>
                                        
                                        <?php
                                        $total = intval($project['TotalTasks']);
                                        $complete = intval($project['TasksComplete']);

                                        if ($total > 0) {
                                            $projectCompletion = round(($complete / $total) * 100);
                                        } else {
                                            $projectCompletion = 0;
                                        }

                                        if ($projectCompletion > 50) {
                                            $inner = "";
                                            $outer = "hidden";
                                        } else {
                                            $inner = "hidden";
                                            $outer = "";
                                        }
                                    
                                        ?>
                                        
                                        <div id="project-<?php echo $project['ProjectID']; ?>" class="bg-white curved-borders p-2">
                                            <h6 id="project-title" class="text-wrap"><?php echo $project['Title']; ?></h6>
                                            <div id="project-progress-bar" class="progress m-1 d-flex" role="progressbar" aria-valuenow="<?php echo $projectCompletion; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <div id="project-progress" class="progress-bar" style="width: <?php echo $projectCompletion; ?>%; background-color: var(--not-complete);"><span class="<?php echo $inner; ?>"><?php echo $projectCompletion; ?>% Complete</span></div>
                                                <span id="progress-bar-value" class="<?php echo $outer; ?>">ㅤ<?php echo $projectCompletion; ?>% Complete</span>
                                            </div>
                                        </div> 

                                    <?php endforeach; ?>
                                    
                                    </div>
                                </div>

                            </div>

                            <div id="task-metrics" class="p-3 col-lg-6 col-md-7 col-sm-12">

                                <?php if ($userTotalTasks > 0): ?>

                                    <h4 class="p-2 mb-2 bold">Task Metrics</h4>
                                    <div id="specific-employee-metrics" class="d-flex justify-content-around">
                                        
                                        <div id="average-task-completed-container" class="metric-sub-section curved-borders col-7 p-3 fill-y">
                                            <div class="metric-inner-sub-section curved-borders h-100 p-2 pt-3 pb-4 d-flex flex-column justify-content-between fill-y">
                                                <?php
                                                    if ($userProjectTaskCompletionDiff == 0) 
                                                    {
                                                        $textWord = "Average";
                                                        $textClass = "black-text";
                                                        $text1 = "You are exactly average.";
                                                        $text2  = "You completed the same number of tasks as the company average.";
                                                    } 
                                                    else 
                                                    {
                                                        $isNegative = ($userProjectTaskCompletionDiff < 0);
                                                        $textWord   = $isNegative ? "Less" : "More";
                                                        $textClass  = $isNegative ? "red-text" : "green-text";
                                                        $text1 = "You completed ";
                                                        $text2 =  " tasks than the <br> average employee.";
                                                    }
                                                ?>
                                            
                                                <h4><?php echo $text1; ?></h4>

                                                <h5>
                                                    <span id="task-variance"><?php echo abs(round($userProjectTaskCompletionDiff, 2)); ?></span><br>
                                                    <span class="<?php echo $textClass; ?>"><?php echo $textWord; ?></span> 
                                                    <?php echo $text2; ?>
                                                </h5>

                                                <div class="task-scale">
                                                    <div class="scale-bar">
                                                        <div class="segment scale-red"></div>
                                                        <div class="segment scale-orange"></div>
                                                        <div class="segment scale-yellow"></div>
                                                        <div class="segment scale-light-green"></div>
                                                        <div class="segment scale-green"></div>
                                                        <div id="task-scale-needle" style="left: clamp(0%, <?php echo max(0, min(100, ($userProjectTaskCompletionDiff + 3) / 6 * 100)); ?>%, 100%);"></div>
                                                    </div>

                                                    <div class="scale-labels">
                                                        <span class="scale-extreme">3</span>
                                                        <span class="scale-mid">2</span>
                                                        <span class="scale-low">1</span>
                                                        <span>0</span>
                                                        <span class="scale-low">1</span>
                                                        <span class="scale-mid">2</span>
                                                        <span class="scale-extreme">3</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="h-100 col-4 metric-sub-section curved-borders p-2">
                                            <div class="metric-inner-sub-section gauge-container">
                                                <canvas id="employee-general-task-gauge" class="task-gauge"></canvas>
                                                <script>
                                                    const employeeNotCompleted = <?php echo $userNotCompletedTasks; ?>;
                                                    const employeeCompleted = <?php echo $userCompletedTasks; ?>;
                                                    const employeeOverdue = <?php echo $userOverdueTasks; ?>;

                                                    new Chart(document.getElementById("employee-general-task-gauge"), {
                                                        type: "doughnut",
                                                        data: {
                                                            datasets: [{
                                                                data: [employeeCompleted, employeeOverdue, employeeNotCompleted],
                                                                backgroundColor: ["#007BFF", "#C82333", "#d9d9d9"],
                                                                borderWidth: 0
                                                            }]
                                                        },
                                                        options: {
                                                            responsive: true,
                                                            aspectRatio: 1,
                                                            cutoutPercentage: 88,
                                                            circumference: 1.3 * Math.PI,
                                                            rotation: 0.85 * Math.PI,
                                                            legend: { display: false },
                                                            tooltips: { enabled: false },
                                                            layout: {
                                                                padding:0
                                                            }
                                                        }
                                                    });
                                                </script>
                                                <div class="gauge-text-overlay">
                                                    <h1 id="user-task-count" class="mt-3">
                                                        <span id="users-completed-task-count"><?php echo $userCompletedTasks; ?></span>/<span id="users-total-task-count"><?php echo $userTotalTasks; ?></span>
                                                    </h1>
                                                    <h2 id="tasks-completed">Tasks<br>Completed</h2>
                                                </div>
                                                
                                            </div>
                                            <h4 class="red-pop-out-text">Expected Tasks: <span id="user-expected-task-count"><?php echo $userCompletedTasks + $userOverdueTasks; ?></span></h4>
                                            
                                        </div>

                                        

                                    </div>
                                    <?php 
                                    if ($userProjectTaskCompletionDiff < $standardDeviationLimit) :// If user is more than 1 standard deviation below average, suggest training { ?>
                                    <h2 id="training" class ="red-pop-out-text p-1 mx-3">This Employee may need training!</h2>
                                    <?php endif; ?>

                                <?php elseif ($userTotalTasks == 0) : ?>
                                    <h2 id="no-tasks" class ="red-pop-out-text p-1 mx-3">No Tasks Assigned</h2>
                                <?php endif; ?>

                                
                            </div>

                            
                        </div>
                        <br>
                        <br>
                        <!-- Metrics bottom row: Overdue Tasks -->
                         <?php if ($userOverdueTasks > 0): ?>
                            <div id="overdue-tasks" class="p-3">
                                <div class="metric-sub-section metric-sub-section-title p-2">
                                    <h4>Overdue Tasks (<span id="user-overdue-tasks"><?php echo ($userOverdueTasks); ?></span>)</h4>
                                </div>
                                <div class="overdue-tasks metric-sub-section p-3 gap-2 d-flex flex-column">
                                    <div id="overdue-tasks-container" class="metric-sub-section custom-scrollbar px-3 py-1 gap-2 d-flex flex-column">
                                        <?php foreach($userOverdueProjectTasks as $task): ?>
                                            <div id="task-<?php echo $task['TaskID']; ?>" class="task card w-100">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h3 id="task-title" class="card-title m-0"><?php echo $task['Title']; ?></h3>
                                                    
                                                    <div class="d-flex">
                                                        <?php 
                                                        if ($task['Priority'] == 1)
                                                        {
                                                            echo "<span id=\"task-priority\" class=\"badge m-2 text-bg-info\">Low Priority</span>";
                                                        }
                                                        else if ($task['Priority'] == 2)
                                                        {
                                                            echo "<span id=\"task-priority\" class=\"badge m-2 text-bg-warning\">Medium Priority</span>";
                                                        }
                                                        else if ($task['Priority'] == 3)
                                                        {
                                                            echo "<span id=\"task-priority\" class=\"badge m-2 text-bg-danger\">High Priority</span>";
                                                        }
                                                        ?>

                                                        <button id="delete-task" class="btn btn-danger" onclick="DeleteTask(<?php echo $task['TaskID']; ?>)">🗑️</button>
                                                    </div>
                                                </div>

                                                <div id="task-description" class="card-body">
                                                    <?php echo $task['Description']; ?>
                                                </div>

                                                <div class="card-footer d-flex justify-content-between align-items-center">
                                                    <!-- Leave element but clear inner html. this will ensure deadline still gets pushed to the end. -->
                                                    
                                                    <div class="d-flex align-items-center">
                                                        <span id="deadline">Deadline: <strong><?php echo $task['Deadline']; ?></strong></span>
                                                        <button 
                                                            id="status" 
                                                            class="btn btn-primary p-2 m-2 <?php echo strtolower(str_replace(' ', '-', $task['Status'])); ?>-btn contrast-text" 
                                                            onclick="UpdateStatus(<?php echo $task['TaskID']; ?>)"><?php echo $task['Status']; ?>
                                                        </button>
                                                    </div>
                                                    
                                                </div>
                                            </div>

                                        <?php endforeach; ?>     
                                                    
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php else: ?>
                    <div class="d-flex flex-column justify-content-center align-items-center gap-3 p-5">
                        <h2 class="red-pop-out-text">Select an employee to view their metrics.</h2>
                    </div>
                <?php endif; ?>

            </div>
        </section>

    <?php endif; ?>

</section> 


<!-- Dashboard: Personal Tasks -->
<section id="personal-tasks" class="titled panel mx-auto">
    <h5 class="panel-title p-3">
        Personal Tasks
    </h5>

    <!-- Create new task -->
    <section id="create-task" class="text-center bg-secondary-orange">
        <button id="btn-add-task" class="btn btn-primary text-nowrap m-3" data-bs-toggle="modal" data-bs-target="#create-task-modal">+ Add Task</button>

        <!-- Task Creation Modal -->
        <div class="modal fade" id="create-task-modal" tabindex="-1" aria-labelledby="create-task-title" style="" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered ">
            <form id="create-task-form"  class="modal-content shadow" style="">
            
            <!-- Modal Header-->
            <div class="modal-header">
                <h1 id="create-task-title" class="modal-title fs-5">Create Task</h1>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Create Task Inputs -->
            <div class="modal-body d-flex flex-column gap-2" style="padding-bottom: 0;">
                <div id="top-row" class="gap-2">
                    <input name="create-task-title" type="text" class="input-box" placeholder="Task Title...">

                    <select name="priority" id="priority" style="width: 130px;">
                        <option selected="" disabled="">Task Priority</option>
                        <option value="1">Low Priority</option>
                        <option value="2">Medium Priority</option>
                        <option value="3">High Priority</option>
                    </select>
                </div>

                <textarea name="description" placeholder="Task Description..." style="height: 103px;"></textarea>

                <div id="bottom-row" class="d-flex flex-row align-items-center justify-content-end">
                    <label for="deadline" class="form-label me-2 mb-0">Deadline:</label>
                    <input type="date" class="form-control" id="deadline" name="deadline" style="width: 180px;">
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary shadow-sm">Create Task</button>
                <button type="button" class="btn btn-danger shadow-sm" data-bs-dismiss="modal">Cancel</button>
            </div>
            </form>
            </div>
        </div>
    </section>

    <!-- All of a user's personal tasks -->
    <section id="task-container" class="d-flex flex-column justify-content-center p-3">
        
        <!-- Iterate over user tasks and display -->
        <?php if ($userPersonalTasks): ?>
            <?php foreach ($userPersonalTasks as $task): ?>
                <div id="task-<?php echo $task['TaskID']; ?>" class="task card w-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 id="task-title" class="card-title m-0"><?php echo htmlspecialchars($task['Title']); ?></h3>
                        
                        <div class="d-flex">
                            <span id="task-priority" class="badge m-2 text-bg-<?php echo $task['Priority'] == 1 ? 'info' : ($task['Priority'] == 2 ? 'warning' : 'danger'); ?>">
                                <?php 
                                    echo $task['Priority'] == 1 ? 'Low Priority' : 
                                            ($task['Priority'] == 2 ? 'Medium Priority' : 'High Priority'); 
                                ?>
                            </span>
                            <button id="delete-task" class="btn btn-danger" onclick="DeleteTask(<?php echo $task['TaskID']; ?>)">🗑️</button>
                        </div>
                    </div>

                    <div id="task-description" class="card-body">
                        <?php echo htmlspecialchars($task['Description']); ?>
                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <div class="d-flex align-items-center">
                            <span id="deadline">Deadline: <strong><?php echo htmlspecialchars($task['Deadline']); ?></strong></span>
                            <button 
                            id="status" 
                            class="btn btn-primary p-2 m-2 <?php echo strtolower(str_replace(' ', '-', $task['Status'])); ?>-btn contrast-text" 
                            onclick="UpdateStatus(<?php echo $task['TaskID']; ?>)"><?php echo $task['Status']; ?>
                        </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No tasks available.</p>
        <?php endif; ?>
    </section>
</section>

<!-- Warning Modal -->
<div class="modal fade" id="training-warning-modal" tabindex="-1" aria-labelledby="training-warning-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow curved-borders">
            <!-- Modal Body -->
            <div class="modal-body p-0">

                <div class="warning-message curved-borders d-flex justify-content-center align-items-center flex-wrap p-3 text-center">
                                        
                    <div class="d-flex align-items-center justify-content-center mb-2 me-5">
                        <img src="static/warning.png" alt="!" class="img-fluid me-3" style="width: 35px;">
                        <h5 id="training" class="mb-1 text-nowrap">Are you sure you want to delete this!</h5>
                    </div>
                                        
                    <div class="w-75 d-flex justify-content-around gap-3">
                        <button class="btn btn-outline-danger text-nowrap notify-buttons">
                            No
                        </button>

                        <button class="btn btn-danger text-nowrap notify-buttons">
                            Yes
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

<!-- Management: Notifications & User Management -->
<section id="management" class="panel mx-auto p-4">
<section id="notifications-section" class="d-flex flex-column">
    
    
    <div id="view-notifications" class="titled secondary panel m-0 w-100">
        <h5 class="panel-title p-3  m-0 position-static" style="font-weight: 800;">
            Notifications
        </h5>
        <div id="notifications-containers" class="p-3 scroll">
            <?php 
                $notificiations = GetUserNotifications($_SESSION['userID']);
                if (!is_array($notificiations) || empty($notificiations)) {
                    echo '<p>No notifications</p>';
                }
                else 
                {?>
                    <?php foreach ($notificiations as $notificiation): ?>
                        <!-- Notification 1 -->
                        <div id="notification-<?php echo $notificiation['NotificationID']; ?>" class="task card w-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 id="task-title" class="card-title m-0"><?php echo $notificiation['Title']; ?></h3>
                                <button id="delete-notification" class="btn btn-danger" onclick="DeleteNotification(<?php echo $notificiation['NotificationID']; ?>)"><strong>Delete</strong></button>
                            </div>

                            <div id="notification-description" class="card-body">
                                <?php echo $notificiation['Message']; ?>
                            </div>

                            <div class="card-footer d-flex align-items-center">
                                Sent: <strong><span id="send-date"><?php echo $notificiation['SendDate']; ?></span></strong>
                            </div>
                        </div>
                    <?php endforeach; 
                }?>

        </div>
    </div>

    <script>
        function DeleteNotification(notificationID) 
        {
            const formData = new FormData();
            formData.append('action', 'notification');
            formData.append('notificationID', notificationID);

            fetch('delete.php',
            {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete notification: ' + data.error);
                }
            })
            .catch(error => console.error('Error:', error)); 
        }
    </script>
</section>

<!-- Warning Modal -->
<div class="modal fade" id="training-warning-modal" tabindex="-1" aria-labelledby="training-warning-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow curved-borders">
            <!-- Modal Body -->
            <div class="modal-body p-0">

                <div class="warning-message curved-borders d-flex justify-content-center align-items-center flex-wrap p-3 text-center">
                                        
                    <div class="d-flex align-items-center justify-content-center mb-2 me-5">
                        <img src="static/warning.png" alt="!" class="img-fluid me-3" style="width: 35px;">
                        <h5 id="training" class="mb-1 text-nowrap">Are you sure you want to delete this!</h5>
                    </div>
                                        
                    <div class="w-75 d-flex justify-content-around gap-3">
                        <button class="btn btn-outline-danger text-nowrap notify-buttons">
                            No
                        </button>

                        <button class="btn btn-danger text-nowrap notify-buttons">
                            Yes
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

<!-- Account Activation -->
 <?php if ($_SESSION['userType'] == 'Manager'): ?>
<section id="account-activation" class="titled secondary panel">
    <h5 class="panel-title p-3 m-0" style="font-weight: 800;">
        Account Activation
    </h5>
    <div id="accounts-container" class="p-3 scroll">

        <!-- User 1 -->
        <?php 
            $inactiveUsers = GetInactiveUsers();
            if (!is_array($inactiveUsers) || empty($inactiveUsers)) :
                echo '<p>No pending account activations</p>';
            else:
            
            //Check not empty
            foreach ($inactiveUsers as $user):
            ?>
                    <div id="user-id" class="task card">
                        <div class="card-body d-flex justify-content-between align-items-center curved-borders flex-wrap">
                            <span id="user-details"><strong><?php echo $user['Forename'] . ' '. $user['Surname']; ?> / <?php echo $user['Email']; ?></strong></span>
                            <div class="d-flex align-items-center">
                            <div class="d-flex gap-2">
                                <button id="accept-btn" class="btn btn-success" onclick="AcceptUserActivation(<?php echo $user['UserID'] ?>, 'Worker')">Accept Worker</button>
                                <button id="accept-btn" class="btn btn-warning" onclick="AcceptUserActivation(<?php echo $user['UserID'] ?>, 'Manager')">Make Manager</button>
                                <button id="decline-btn" class="btn btn-danger" onclick="DeclineUserActivation(<?php echo $user['UserID'] ?>)">Decline</button>
                            </div>
                            </div>
                        </div>
                    </div>

            <?php 
            endforeach; 
            endif;
        ?>
        
    </div>

    <script>
        function AcceptUserActivation(userID, userType) 
        {
            const formData = new FormData();
            formData.append('action', 'user');
            formData.append('userID',userID);
            formData.append('userType', userType)

            fetch('update.php',
            {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to activate user: ' + data.error);
                }
            })
            .catch(error => console.error('Error:', error));            
        }

        function DeclineUserActivation(userID) 
        {
            const formData = new FormData();
            formData.append('action', 'user');
            formData.append('userID', userID);

            fetch('delete.php',
            {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete user activation request: ' + data.error);
                }
            })
            .catch(error => console.error('Error:', error)); 
        }
    </script>
</section>  
 <?php endif; ?>         
</section>


<script>
document.getElementById("create-task-form")
    .addEventListener("submit", function (e) {

        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        // Append Necessary Data
        formData.append("action", 'task')
        formData.append(
            "projectID", 
            <?php 
            if ($pageType === "project") 
            {
                echo $pageID;
            }
            else 
            {
                echo "'null'";
            }
            ?>
        ); // ProjectID
        formData.append("ownerID", <?php echo $_SESSION['userID']; ?>);

        if (!document.getElementById("user"))
        {
            formData.append("user", <?php echo $_SESSION['userID']; ?>);
        }

        // Execute Post
        fetch("create.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then((data) => {
            if (data.success)
            {
                alert("Task Created successfully!")
                // Hiding Bootstrap modal. Janky bootstrap
                const modal = document.getElementById("create-task-modal");
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();

                location.reload();
            }
            else 
            {
                alert("Failed to create task: " + data.error);
            }
            form.reset();
        })
        .catch(err => console.error(err));
});

function UpdateStatus(taskID) {
    let newStatus = '';
    let currentStatus = document.querySelector(`#task-${taskID} #status`).textContent.trim();

    if (currentStatus === 'Overdue' || currentStatus === 'Not Complete') {
        newStatus = 'Complete';
    } else if (currentStatus === 'Complete') {
        const deadlineText = document.querySelector(`#task-${taskID} #deadline`).textContent.replace('Deadline: ', '').trim();
        const deadlineDate = new Date(deadlineText);
        const currentDate = new Date();
        const isOverdue = deadlineDate < currentDate;
        newStatus = isOverdue ? 'Overdue' : 'Not Complete';
    }

    const formData = new FormData();
    formData.append('action', 'task');
    formData.append('taskID', taskID);
    formData.append('status', newStatus);

    fetch('update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update task status: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

function DeleteTask(taskID)
{
    const formData = new FormData();
    formData.append('action', 'task');
    formData.append('taskID', taskID);

    fetch('delete.php',
    {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to delete task: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}


</script>

<?php endif; ?>

<?php include "footer.php"; ?>