<?php
include "read.php";

session_start();

// Check if user is logged in. // -1 == No User
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
    $_SESSION['userID'] = -1;
    header("Location: dashboard.php");
    exit;
}

if (isset($_GET['page-type']))
{
    $pageType = $_GET['page-type'];
}
else $pageType = "";

if (isset($_GET['id']))
{
    $pageID = $_GET['id'];

    $pageID = is_numeric($_GET['id']) ? intval($_GET['id']) : exit("Error: Invalid Page ID");
} 
else exit("Error: Bad Request - Missing ID Argument");

// TODO: Check User has access to page.

// Cookies Change
if (isset($_COOKIE[$pageType.'1']) && $_COOKIE[$pageType.'1'] == $pageID)
{
    setcookie($pageType.'1', $pageID, time() + (365*24*60*60));
}
else if (isset($_COOKIE[$pageType.'2']) && $_COOKIE[$pageType.'2'] == $pageID)
{
    setcookie($pageType.'2', $pageID, time() + (365*24*60*60));

}
else 
{
    if (isset($_COOKIE[$pageType.'1']))
    {
        setcookie($pageType.'2', $_COOKIE[$pageType.'1'], time() + (365*24*60*60));
    }
    setcookie($pageType.'1', $pageID, time() + (365*24*60*60));

}

if ($pageType == "project")
{
    $projectData  = GetProjectData($pageID)[0];
    $projectUsers = GetProjectMembers($pageID);
    $projectTags  = GetProjectTags($pageID);
    $projectTasks = GetProjectTasks($pageID);

    if ($projectData['TotalTasks'] != 0)
    {
        $projectCompletionPercentage = round($projectData['TasksComplete'] / $projectData['TotalTasks'] * 100);
    } 
    else
    {
        $projectCompletionPercentage = 100;
    }

    $canCreateProjectTask = false;

}
else if ($pageType == "topic")
{
    $topicData  = GetTopicData($pageID)[0];
    $topicTags  = GetTopicTags($pageID);
    $topicUsers = GetTopicMembers($pageID);
    $topicPosts = GetTopicPosts($pageID);

}
else if ($pageType == "post")
{
    $topicID = isset($_GET['topic-id']) ? $_GET['topic-id'] : null;
    $postData     = GetPostData($pageID)[0];
    $postComments = GetPostComments($pageID);
} 
else exit("Error: Bad Request - Incorrect Page Type");


?>

<?php include "header.php" ?>

<!-- View: Project -->
<?php if ($pageType == "project"): ?>
<section id="view-project" class="panel mx-auto p-3">

    <!-- Project Info: Title, Description, Completion status & Tags-->
    <div id="project-info" class="p-3 bg-white curved-borders">
        <!-- Title & Deadline-->
        <div id="project-title-row" class="d-flex justify-content-between">
            
            <div class="d-flex flex-column fill-x">

                <h1 id="project-title" style="width: 75%;"><strong><?php echo $projectData['Title'] ?></strong></h1>

                <!-- Project tags -->
                <div class="d-flex justify-content-left flex-wrap gap-2 p-2 pt-2">
                    <?php 
                    foreach($projectTags as $tag)
                    {
                        $tagName = $tag['Name'];
                        $tagClass = strtolower(str_replace('/', '', $tag["Name"]));

                        echo "<span class=\"big-badge badge bg-{$tagClass}\">{$tagName}</span>";  
                    } 
                    ?>
                </div>
            </div>

            <div>
                <div class="d-flex justify-content-end gap-2 mb-2">
                <?php foreach($projectUsers as $user)
                    {
                        if ($user['UserID'] == $_SESSION['userID'] && $user['Leader'] == true)
                        {
                            echo '<button id="edit-project" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#edit-project-modal">✏️</button>'; 
                        }  
                        if ($user['UserID'] == $_SESSION['userID'] && $_SESSION['userType'] == "Manager")
                        {
                            echo '<button id="delete-project" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delete-warning-modal"><strong>Delete</strong></button>'; 
                        }  
                    } 
                ?>
                </div>
            </div>


        </div>
        <p id="project-description"><?php echo $projectData['Description'] ?></p>

        <!-- Project Completion bar-->
        <div id="completion-status-bar" class="w-100">
            <h5>Completion Status:</h5>

            <span id="totalTasks" class="hidden">2</span>
            <div id="progress-bar" class="progress m-1" role="progressbar" aria-valuenow="<?php echo $projectCompletionPercentage ?>">
                <div id="project-progress" class="progress-bar" style="width: <?php echo $projectCompletionPercentage ?>%; background-color: var(--not-complete);"><?php echo $projectCompletionPercentage ?>%</div>
            </div>
        </div>

        <h5 class="text-center text-nowrap mt-3">Deadline: <strong><span id="project-deadline"><?php echo $projectData['Deadline'] ?></span> </strong></h5>
    </div>

    <!-- Project Members-->
    <section id="project-members" class="titled panel">
        <h5 class="panel-title p-3" style="font-weight: 800;">
            Project Members
        </h5>

        <!-- Project Memebers-->
        <div id="users-containers" class="d-flex flex-wrap p-2 gap-2">
            <?php foreach($projectUsers as $user): 
                if ($user['UserID'] == $_SESSION['userID'] && $user['Leader'] == true)
                {
                    $canCreateProjectTask = true;
                }    

            ?>
            
            <span class="content-box p-2 px-4 m-0 gap-2">
                <?php echo $userName = $user['Forename'] . ' ' . $user['Surname']; ?>
                <?php echo $user['Leader'] ? '<span class="badge bg-non-technical ms-3">Team Leader</span>': '' ?> 
            </span>

            <?php endforeach; ?>
        </div>
    </section>

    <!-- Project Tasks -->
    <section id="project-tasks" class="titled panel">
        
        <h5 class="panel-title p-3" style="font-weight: 800;">
            Project Tasks
        </h5>

        <!-- Task Container-->
        <div id="task-container" class="p-3 scroll">

            <?php if($canCreateProjectTask): ?>
            <button id="btn-add-task" class="btn btn-primary text-nowrap m-3" data-bs-toggle="modal" data-bs-target="#create-task-modal">+ Add Task</button>
            <?php endif; ?>

            <?php foreach($projectTasks as $task): ?>

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
                    <span id="asignee">
                        Assigned to: 
                        <strong><?php echo $task['Forename'] . ' ' . $task['Surname']; ?></strong>
                    </span>
                    
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

    </section>
</section>
<!-- Create new task -->
<section id="create-task" class="text-center bg-secondary-orange">
    
    <!-- Task Creation Modal -->
    <div class="modal fade" id="create-task-modal" tabindex="-1" aria-labelledby="create-task-title" style="" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered ">
        <form id="create-task-form" class="modal-content shadow" style="">
        
        <!-- Modal Header-->
        <div class="modal-header">
            <h1 id="create-task-title" class="modal-title fs-5">Create Task</h1>

            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Create Task Inputs -->
        <div class="modal-body d-flex flex-column gap-2" style="padding-bottom: 0;">
            <div id="top-row" class="gap-2">
                <input name="create-task-title" type="text" class="input-box" placeholder="Task Title...">

                <select name="user" id="user">
                    <option selected="" disabled="">Select User</option>

                    <?php 
                        $users = GetActiveUsers();

                        foreach ($users as $user):
                    ?>
                    <option value="<?php echo $user['UserID']; ?>"><?php echo $user['Forename'] . ' ' . $user['Surname']; ?></option>

                    <?php endforeach; ?>
                </select>

                <select name="priority" id="priority" style="width: 130px;">
                    <option selected="" disabled="">Task Prirority</option>
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

<!-- Edit Project -->
<section id="edit-project">
    <div class="modal fade" id="edit-project-modal" tabindex="-1" aria-labelledby="edit-project-title" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
        <form id="edit-project-form" class="modal-content shadow">

        <input type="hidden" name="projectID" value="<?php echo $pageID; ?>">
        
        <!-- Modal Header-->
        <div class="modal-header">
            <h1 id="edit-project-title" class="modal-title fs-5">Edit Project</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body d-flex flex-column gap-2" style="padding-bottom: 0;">

            <div id="top-row" class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                
                <input name="title" type="text" class="input-box w-100" 
                       placeholder="Project Title..." 
                       value="<?php echo $projectData['Title']; ?>">

                <!-- Team Leaders -->
                <section class="btn-group">
                    <button class="btn dropdown-toggle" type="button" 
                            id="dropdownMenuButton3" 
                            data-bs-toggle="dropdown" 
                            data-bs-auto-close="true" 
                            aria-expanded="false"
                            style="background-color: white; border-color: gray;">
                        Select Team Leaders
                    </button>

                    <div class="dropdown-menu p-3 curved-borders" aria-labelledby="dropdownMenuButton3">
                        <?php 
                            $users = GetActiveUsers();
                            foreach ($users as $user):

                                $isProjectLeader = false;
                                foreach ($projectUsers as $member) {
                                    if ($member['UserID'] == $user['UserID'] && $member['Leader']) {
                                        $isProjectLeader = true;
                                        break;
                                    }
                                }
                        ?>
                        <div class="form-check d-flex align-items-center gap-2">
                            <input name="team-leaders[]" class="form-check-input" type="checkbox"
                                   value="<?php echo $user['UserID']; ?>"
                                   id="user<?php echo $user['UserID']; ?>-checkbox"
                                   <?php echo $isProjectLeader ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="user<?php echo $user['UserID']; ?>-checkbox">
                                <?php echo $user['Forename'] . ' ' . $user['Surname']; ?>
                            </label>
                        </div>
                        <hr class="dropdown-divider">
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Project Members -->
                <section class="btn-group">
                    <button class="btn dropdown-toggle" type="button" 
                            id="dropdownMenuButton5" 
                            data-bs-toggle="dropdown" 
                            data-bs-auto-close="true" 
                            aria-expanded="false"
                            style="background-color: white; border-color: gray;">
                        Select Project Members
                    </button>

                    <div class="dropdown-menu p-3 curved-borders" aria-labelledby="dropdownMenuButton5">
                        <?php 
                            $users = GetActiveUsers();
                            foreach ($users as $user):

                                $isProjectUser = false;
                                foreach ($projectUsers as $member) {
                                    if ($member['UserID'] == $user['UserID'] && !$member['Leader']) {
                                        $isProjectUser = true;
                                        break;
                                    }
                                }
                        ?>
                        <div class="form-check d-flex align-items-center gap-2">
                            <input name="users[]" class="form-check-input" type="checkbox"
                                   value="<?php echo $user['UserID']; ?>"
                                   id="user<?php echo $user['UserID']; ?>-checkbox"
                                   <?php echo $isProjectUser ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="user<?php echo $user['UserID']; ?>-checkbox">
                                <?php echo $user['Forename'] . ' ' . $user['Surname']; ?>
                            </label>
                        </div>
                        <hr class="dropdown-divider">
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div id="middle-row" class="d-flex justify-content-center mb-2">
                <section class="btn-group">
                    <button class="btn dropdown-toggle" type="button" 
                            id="dropdownMenuButton4" 
                            data-bs-toggle="dropdown" 
                            data-bs-auto-close="true" 
                            aria-expanded="false"
                            style="background-color: white; border-color: gray;">
                        Select Tags
                    </button>

                    <div class="dropdown-menu p-3 curved-borders" aria-labelledby="dropdownMenuButton4">
                        <?php 
                            $tags = GetTags();
                            foreach ($tags as $tag):

                                $isProjectTag = false;
                                foreach ($projectTags as $projTag) {
                                    if ($projTag['TagID'] == $tag['TagID']) {
                                        $isProjectTag = true;
                                        break;
                                    }
                                }
                        ?>
                        <div class="form-check d-flex align-items-center gap-2">
                            <input name="tags[]" class="form-check-input" type="checkbox"
                                   value="<?php echo $tag['TagID']; ?>"
                                   id="tag-<?php echo $tag['TagID']; ?>-checkbox"
                                   <?php echo $isProjectTag ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="tag-<?php echo $tag['TagID']; ?>-checkbox">
                                <?php echo $tag['Name']; ?>
                            </label>
                        </div>
                        <hr class="dropdown-divider">
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- Description -->
            <textarea name="description" placeholder="Enter Description..." style="height: 103px;"><?php echo $projectData['Description']; ?></textarea>

            <div id="bottom-row" class="d-flex flex-row align-items-center justify-content-end">
                <label for="deadline" class="form-label me-2 mb-0">Deadline:</label>
                <input type="date" class="form-control" id="deadline" name="deadline"
                       style="width: 180px;"
                       value="<?php echo $projectData['Deadline']; ?>">
            </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary shadow-sm">Edit Project</button>
            <button type="button" class="btn btn-danger shadow-sm" data-bs-dismiss="modal">Cancel</button>
        </div>

        </form>
        </div>
    </div>
</section>

<!-- Delete Warning Modal -->
<div class="modal fade" id="delete-warning-modal" tabindex="-1" aria-labelledby="delete-warning-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow curved-borders">
            <!-- Modal Body -->
            <div class="modal-body p-0">

                <div class="warning-message curved-borders d-flex justify-content-center align-items-center flex-wrap p-5 text-center">
                                        
                    <div class="d-flex align-items-center justify-content-center mb-2 me-5">
                        <img src="static/warning.png" alt="!" class="img-fluid me-3" style="width: 35px;">
                        <h5 id="delete-warning-title" class="mb-1 text-nowrap">Are you sure you want to delete this project?</h5>
                    </div>
                                        
                    <div class="w-75 d-flex justify-content-around gap-3">
                        <button class="btn btn-outline-danger text-nowrap notify-buttons">
                            No
                        </button>

                        <button class="btn btn-danger text-nowrap notify-buttons" onclick="DeleteProject(<?php echo $pageID; ?>)">
                            Yes
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>


<!-- Create Task -->
<script>
document.getElementById("create-task-form")
.addEventListener("submit", function (e) {

    e.preventDefault();

    const form = e.target;

    // Elements
    const titleInput = form.querySelector('input[name="create-task-title"]');
    const userSelect = form.querySelector('select[name="user"]');
    const prioritySelect = form.querySelector('select[name="priority"]');
    const descriptionInput = form.querySelector('textarea[name="description"]');
    const deadlineInput = form.querySelector('input[name="deadline"]');

    let missingFields = [];

    // Remove previous error styles
    titleInput.classList.remove("input-error");
    userSelect.classList.remove("input-error");
    prioritySelect.classList.remove("input-error");
    descriptionInput.classList.remove("input-error");
    deadlineInput.classList.remove("input-error");

    // Validate Title
    if (!titleInput.value.trim()) {
        missingFields.push("Title");
        titleInput.classList.add("input-error");
    }

    // Validate User (check if first option still selected)
    if (userSelect.selectedIndex === 0) {
        missingFields.push("Assigned User");
        userSelect.classList.add("input-error");
    }

    // Validate Priority (check if first option still selected)
    if (prioritySelect.selectedIndex === 0) {
        missingFields.push("Priority");
        prioritySelect.classList.add("input-error");
    }

    // Validate Description
    if (!descriptionInput.value.trim()) {
        missingFields.push("Description");
        descriptionInput.classList.add("input-error");
    }

    // Validate Deadline
    if (!deadlineInput.value.trim()) {
        missingFields.push("Deadline");
        deadlineInput.classList.add("input-error");
    }

    // If invalid, show error message
    if (missingFields.length > 0) {

        let errorDiv = form.querySelector(".form-error-message");

        if (!errorDiv) {
            errorDiv = document.createElement("div");
            errorDiv.className = "form-error-message text-danger mb-2";
            form.querySelector(".modal-body").prepend(errorDiv);
        }

        errorDiv.textContent =
            "Please fill in the following fields: " + missingFields.join(", ");

        return;
    }

    // Remove error message if valid
    const errorDiv = form.querySelector(".form-error-message");
    if (errorDiv) errorDiv.remove();

    // Prepare FormData
    const formData = new FormData(form);
    formData.append("action", 'task');
    formData.append(
        "projectID",
        <?php 
            if ($pageType === "project") {
                echo $pageID;
            } else {
                echo "'null'";
            }
        ?>
    );
    formData.append("ownerID", <?php echo $_SESSION['userID']; ?>);

    // Submit form
    fetch("create.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then((data) => {
        if (data.success) {

            const modal = document.getElementById("create-task-modal");
            const modalInstance = bootstrap.Modal.getInstance(modal);
            modalInstance.hide();

            form.reset();
            location.reload();

        } else {
            alert("Failed to create task: " + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert("An unexpected error occurred. Please try again.");
    });

});

</script>

<!-- Update Task Status & Delete Task -->
<script>
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

<!-- Update Project & Delete Project -->
<script>
    document.getElementById("edit-project-form")
    .addEventListener("submit", function (e) {

        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        // Append Necessary Data
        formData.append("action", 'project')

        // Execute Post
        fetch("update.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then((data) => {
            if (data.success)
            {
                alert("Project Updated successfully!")

                const modal = document.getElementById("edit-project-modal");
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();

                location.reload();
            }
            else 
            {
                alert("Failed to update project: " + data.error);
            }
            form.reset();
        })
        .catch(err => console.error(err));
});

function DeleteProject(projectID)
{
    const formData = new FormData();
    formData.append('action', 'project');
    formData.append('projectID', projectID);

    fetch('delete.php',
    {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'search.php?type=projects';
        } else {
            alert('Failed to delete project: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
<?php endif; ?>

<!-- View: Knowledge/Topic -->
<?php if ($pageType == "topic"): ?>
<section id="view-knowledge" class="panel mx-auto p-3">
    <!-- Knowledge Title -->
    <h1 id="knowledge-title" class="p-3 text-center bold-weight">
        <?php echo $topicData['Title']; ?>
    </h1>

    <!-- Knowledge Tags -->
    <div class="w-100 d-flex justify-content-center">
        <div id="knowledge-tags" class="w-50 d-flex flex-wrap justify-content-center gap-2 px-3">
            <?php 
            foreach($topicTags as $tag)
            {
                $tagName = $tag['Name'];
                $tagClass = strtolower(str_replace('/', '', $tag["Name"]));

                echo "<span class=\"badge big-badge bg-{$tagClass}\">{$tagName}</span>";  
            } 
            ?>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-2">
    <?php
        if ($_SESSION['userType'] == "Manager")
        {
            echo '<button id="edit-topic" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#edit-knowledge-modal">✏️</button>';
            echo '<button id="delete-project" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delete-warning-modal"><strong>Delete</strong></button>'; 
        }     
    ?>
    </div>


    <!-- Knowledge Members -->
    <div id="knowledge-members-container" class="p-3">
        <h6 class="bold-weight">Knowledge/Topic Members: </h6>

        <div id="users-containers" class="d-flex flex-wrap m-2 gap-2">
            <?php foreach($topicUsers as $user): ?>
            
            <span class="content-box p-2 px-4 m-0 gap-2">
                <?php echo $userName = $user['Forename'] . ' ' . $user['Surname']; ?>
            </span>

            <?php endforeach; ?>
            
        </div>
    </div>

    <!-- Posts List -->
    <section id="posts-container">
        <!-- Create Post -->
        <div class="nav-search top-curved p-2 px-4">
            <button id="create-post" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#create-post-modal">
                + Create Post
            </button>
        </div>

        <!-- Posts -->
        <div id="knowledge-posts-container" class="d-flex flex-wrap results-container gap-3">
            <?php foreach ($topicPosts as $post): ?>
            <!-- Example Post Card-->
            <div id="project-id" class="card">
                <div class="card-body">
                    <h5 id="post-title" class="card-title">
                        <?php echo $post['Title']; ?>   
                    </h5>

                    <p id="post-description"><?php echo $post['Message']; ?></p>
                    
                </div>
                <div class="card-footer">
                    <a id="project-link" href="display.php?page-type=post&id=<?php echo $post['PostID']; ?>&topic-id=<?php echo $pageID ?>" class="btn btn-primary">View Post</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</section>

<!-- Create: Knowledge Post Modal -->
<div class="modal fade" id="create-post-modal" tabindex="-1" aria-labelledby="create-post-title" style="" aria-modal="true" role="dialog">
    <div id="create-post-form" class="modal-dialog modal-dialog-centered ">
    <form id="create-post-form" class="modal-content shadow" enctype="multipart/form-data" style="">
    
    <!-- Modal Header-->
    <div class="modal-header">
        <h1 id="create-post-title" class="modal-title fs-5">Create Post</h1>

        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <!-- Create post Inputs -->
    <div class="modal-body d-flex flex-column gap-2" style="padding-bottom: 0;">
        <div id="top-row" class="gap-2">
            <input name="title" type="text" class="input-box w-100" placeholder="Post Title...">
        </div>

        <textarea name="message" placeholder="Enter Text..." style="height: 103px;"></textarea>

        <input name="file" type="file">
    </div>

    <!-- Footer -->
    <div class="modal-footer">
        <button type="submit" class="btn btn-primary shadow-sm">Publish Post</button>
        <button type="button" class="btn btn-danger shadow-sm" data-bs-dismiss="modal">Cancel</button>
    </div>
    </form>
    </div>
</div>

<!-- Edit Knowledge/Topic/Post -->
<section id="edit-knowledge">
    <!-- Knowledge Creation Modal -->
    <div class="modal fade" id="edit-knowledge-modal" tabindex="-1" aria-labelledby="edit-knowledge-title" style="" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered ">
        <form id="edit-knowledge-form" class="modal-content shadow" style="">

        <input type="hidden" name="topicID" value="<?php echo $pageID; ?>">

        <!-- Modal Header-->
        <div class="modal-header">
            <h1 id="edit-knowledge-title" class="modal-title fs-5">Edit Knowledge Topic</h1>

            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Create Task Inputs -->
        <div class="modal-body d-flex flex-column gap-2" style="padding-bottom: 0;">
            
            <!-- Knowledge Title -->
            <input name="title" type="text" class="input-box w-100" placeholder="Post Title..." value="<?php echo $topicData['Title'] ?>">

            <div id="top-row" class="d-flex justify-content-center gap-2">
                <!-- Select Teams -->
                <section class="btn-group" style="width: 225px;">
                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" style="background-color: white; border-color: gray;">
                        Modify Access
                    </button>
                    <div class="dropdown-menu p-3 curved-borders" aria-labelledby="dropdownMenuButton1">

                        <?php 
                            $users = GetActiveUsers();

                            foreach ($users as $user):
                                
                                $isTopicUser = false;
                                foreach ($topicUsers as $topicUser)
                                {
                                    if ($topicUser['UserID'] == $user['UserID'])
                                    {
                                        $isTopicUser = true;
                                        break;
                                    }
                                }
                        ?>

                        <div class="form-check d-flex align-items-center gap-2">
                            <input name="users[]" class="form-check-input" type="checkbox" value="<?php echo $user['UserID']; ?>" id="user-<?php echo $user['UserID']; ?>-checkbox" <?php echo $isTopicUser ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="user-<?php echo $user['UserID']; ?>-checkbox">
                                <?php echo $user['Forename'] . ' ' . $user['Surname']; ?>
                            </label>
                        </div>

                        <hr class="dropdown-divider">
                        <?php endforeach; ?>

                    </div>
                    
                </section>

                <!-- Select Tags -->
                <section class="btn-group" style="width: 125px;">

                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" style="background-color: white; border-color: gray;">
                        Select Tags
                    </button>
                    <div class="dropdown-menu p-3 curved-borders" aria-labelledby="dropdownMenuButton2">
                        
                        <?php 
                            $tags = GetTags();

                            foreach ($tags as $tag):

                                $isTopicTag = false;
                                foreach ($topicTags as $topicTag)
                                {
                                    if ($topicTag['TagID'] == $tag['TagID'])
                                    {
                                        $isTopicTag = true;
                                        break;
                                    }
                                }
                        ?>
                        <div class="form-check d-flex align-items-center gap-2">
                            <input name="tags[]" class="form-check-input" type="checkbox" value="<?php echo $tag['TagID']; ?>" id="tag-<?php echo $tag['TagID']; ?>-checkbox" <?php echo $isTopicTag ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="tag-<?php echo $tag['TagID']; ?>-checkbox">
                                <?php echo $tag['Name']; ?>
                            </label>
                        </div>

                        <hr class="dropdown-divider">
                        <?php endforeach; ?>

                    </div>
                </section>
                
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary shadow-sm">Edit Knowledge Topic</button>
            <button type="button" class="btn btn-danger shadow-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
        </form>
        </div>
    </div>

</section>

<!-- Delete Topic Warning Modal -->
<div class="modal fade" id="delete-warning-modal" tabindex="-1" aria-labelledby="delete-warning-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow curved-borders">
            <!-- Modal Body -->
            <div class="modal-body p-0">

                <div class="warning-message curved-borders d-flex justify-content-center align-items-center flex-wrap p-5 text-center">
                                        
                    <div class="d-flex align-items-center justify-content-center mb-2 me-5">
                        <img src="static/warning.png" alt="!" class="img-fluid me-3" style="width: 35px;">
                        <h5 id="delete-warning-title" class="mb-1 text-nowrap">Are you sure you want to delete this topic?</h5>
                    </div>
                                        
                    <div class="w-75 d-flex justify-content-around gap-3">
                        <button class="btn btn-outline-danger text-nowrap notify-buttons">
                            No
                        </button>

                        <button class="btn btn-danger text-nowrap notify-buttons" onclick="DeleteTopic(<?php echo $pageID; ?>)">
                            Yes
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

<!-- Update Topic & Delete Topic-->
<script>
    document.getElementById("edit-knowledge-form")
    .addEventListener("submit", function (e) {

        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        // Append Necessary Data
        formData.append("action", 'topic')

        // Execute Post
        fetch("update.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then((data) => {
            if (data.success)
            {
                alert("Topic Updated successfully!")

                const modal = document.getElementById("edit-knowledge-modal");
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();

                location.reload();
            }
            else 
            {
                alert("Failed to update topic: " + data.error);
            }
            form.reset();
        })
        .catch(err => console.error(err));
});

function DeleteTopic(topicID)
{
    const formData = new FormData();
    formData.append('action', 'topic');
    formData.append('topicID', topicID);

    fetch('delete.php',
    {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'search.php?type=knowledge';
        } else {
            alert('Failed to delete topic: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
<?php endif; ?>

<!-- View: Post -->
<?php if ($pageType == "post"): ?>
    <input type="hidden" id="topic-ID" value="<?php echo $pageID; ?>">
<section id="view-post" class="panel mx-auto p-3">
    <div id="post-id" class="card w-100 d-flex flex-column gap-2 m-0 mb-3">
        
        <div class="d-flex justify-space-between card-header px-3">
            <p id="post-author" class="mb-0">
                <?php echo $postData['Forename'] . ' '. $postData['Surname']; ?>    
            </p>

            <div class="vr mx-2"></div>

            <p id="post-creation-date" class="mb-0">
                <?php echo $postData['CreatedOn']; ?>
            </p>
        </div>

        <div class="p-3 pt-0">
            <h1 id="post-title" class="bold-weight pb-2">
                <?php echo $postData['Title']; ?>
            </h1>

            <p id="post-text" class="mb-0" style="min-height: max-content;"><?php echo $postData['Message']; ?></p>

        </div>
            <div class="card-footer pb-3 d-flex align-items-center  ">
                <a id="post-file" class="link" href="download.php?file=<?php echo urlencode($postData['File']); ?>">
                    Attached File: <?php echo htmlspecialchars($postData['File']); ?>
                </a>
            </div>
    </div>
    
    <!-- Create Reply Button -->
    <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#create-post-modal">Reply +</button>

    <!-- Responses container -->
    <div id="responses" class="d-flex flex-column results-container curved-borders p-4 gap-3" style="min-height: 200px;">

        <?php foreach ($postComments as $reply): ?>
        <!-- Example Response 1-->
        <div id="post-<?php echo $reply['PostID']; ?>" class="card w-100 d-flex flex-column gap-2 m-0">

            <div class="d-flex justify-space-between card-header px-3">
                <p id="post-author" class="mb-0">
                    <?php echo $reply['Forename'] . ' '. $reply['Surname']; ?>    
                </p>

                <div class="vr mx-2"></div>

                <p id="post-creation-date" class="mb-0">
                    <?php echo $reply['CreatedOn']; ?>
                </p>
            </div>

            <div class="p-3 pt-0">
                <h3 id="post-title" class="bold-weight pb-2"><?php echo $reply['Title']; ?></h3>

                <p id="post-text" class="mb-0" style="min-height: max-content;"><?php echo $reply['Message']; ?></p>

            </div>
            <div class="card-footer pb-3 d-flex align-items-center  ">
                <a id="post-file" class="link" href="download.php?file=<?php echo urlencode($reply['File']); ?>">
                    Attached File: <?php echo htmlspecialchars($reply['File']); ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<!-- Knowledge Post Modal -->
<div class="modal fade" id="create-post-modal" tabindex="-1" aria-labelledby="create-post-title" style="" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered ">
    <form id="create-post-form" class="modal-content shadow" enctype="multipart/form-data" style="">
    
    <!-- Modal Header-->
    <div class="modal-header">
        <h1 id="create-post-title" class="modal-title fs-5">Create Post</h1>

        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <!-- Create post Inputs -->
    <div class="modal-body d-flex flex-column gap-2" style="padding-bottom: 0;">
        <div id="top-row" class="gap-2">
            <input name="title" type="text" class="input-box w-100" placeholder="Post Title...">
        </div>

        <textarea name="message" placeholder="Enter Text..." style="height: 103px;"></textarea>

        <input name="file" type="file">
    </div>

    <!-- Footer -->
    <div class="modal-footer">
        <button type="submit" class="btn btn-primary shadow-sm">Publish Post</button>
        <button type="button" class="btn btn-danger shadow-sm" data-bs-dismiss="modal">Cancel</button>
    </div>
    </form>
    </div>
</div>


<?php endif; ?>

<?php if ($pageType == "topic" || $pageType == "post"): ?>

<script>
document.getElementById("create-post-form")
.addEventListener("submit", function (e) {

    e.preventDefault();

    const form = e.target;

    // Elements
    const titleInput = form.querySelector('input[name="title"]');
    const messageInput = form.querySelector('textarea[name="message"]');

    let missingFields = [];

    // Remove previous error styles
    titleInput.classList.remove("input-error");
    messageInput.classList.remove("input-error");

    // Validate Title
    if (!titleInput.value.trim()) {
        missingFields.push("Title");
        titleInput.classList.add("input-error");
    }

    // Validate Message/Description
    if (!messageInput.value.trim()) {
        missingFields.push("Description");
        messageInput.classList.add("input-error");
    }

    // If invalid, show error message
    if (missingFields.length > 0) {

        let errorDiv = form.querySelector(".form-error-message");

        if (!errorDiv) {
            errorDiv = document.createElement("div");
            errorDiv.className = "form-error-message text-danger mb-2";
            form.querySelector(".modal-body").prepend(errorDiv);
        }

        errorDiv.textContent =
            "Please fill in the following fields: " + missingFields.join(", ");

        return;
    }

    // Remove error message if valid
    const errorDiv = form.querySelector(".form-error-message");
    if (errorDiv) errorDiv.remove();

    // Prepare FormData
    const formData = new FormData(form);
    formData.append("action", 'post');
    formData.append("parentID", 
        <?php
        if ($pageType === "topic") {
            echo "'null'";
        } else if ($pageType === "post") {
            echo $pageID;
        }
        ?>
    );
    formData.append("topicID", 
        <?php
        if ($pageType === "topic") {
            echo $pageID;
        } else if ($pageType === "post") {
            echo $topicID;
        }
        ?>
    );
    formData.append("ownerID", <?php echo $_SESSION['userID']; ?>);

    // Submit form
    fetch("create.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then((data) => {
        if (data.success) {

            const modal = document.getElementById("create-post-modal");
            const modalInstance = bootstrap.Modal.getInstance(modal);
            modalInstance.hide();

            location.reload();

        } else {
            alert("Failed to create post: " + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert("An unexpected error occurred. Please try again.");
    });

});

</script>
<?php endif; ?>

<?php include "footer.php" ?>