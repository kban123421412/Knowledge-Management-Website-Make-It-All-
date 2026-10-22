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

if (isset($_GET['type'])) 
{
    $type = $_GET['type'];
}
else  $type = "";


if ($type == "projects")
{
    $userProjects = GetUserProjects($_SESSION["userID"]);
}
else if ($type == "knowledge")
{
    $userKnowledge = GetUserTopics($_SESSION["userID"]);
}
else
{
    exit("Eror: Bad Request");
}


?>

<?php include "header.php" ?>

<!-- Search: Generic Search + Results Container -->
<section id="search" class="panel mx-auto">
    <!-- Search Parameters -->
    <div id="search-bar" class="d-flex gap-3 p-3">
        <input id="search-text" class="input-box" style="width: 375px;" type="text" placeholder="Search..">
        <?php if ($userType == "Manager"): ?>
        <?php if ($type == "projects"): ?>
            <button id="btn-add-project" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#create-project-modal">+ Create Project</button>
        <?php elseif ($type == "knowledge"): ?>
            <button id="btn-add-knowledge" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#create-knowledge-modal">+ Create Knowledge Topic</button>
        <?php endif; ?>
        <?php endif; ?>
        
    </div>

    <?php if ($type != "projects"): ?>
    <div id="filters" class="d-flex flex-wrap gap-2 p-3" style="padding-top: 0 !important;">
        <?php 
        $tags = GetTags();

        foreach ($tags as $tag)
        {
            $tagID = $tag["TagID"];
            $tagText = $tag["Name"];
            $tagClass = strtolower(str_replace('/', '', $tag["Name"]));
            echo "<span id=\"{$tagID}\" class=\"badge badge-toggle bg-{$tagClass}\">{$tagText}</span>";
        }
        ?>
    </div>
    <?php endif; ?>
    
    <!-- Search Results Container-->
    <div id="search-results" class="d-flex flex-wrap results-container">

        <?php if (isset($userProjects) && $userProjects): ?>
        <?php foreach ($userProjects as $project): ?>
        <div class="search-item col-lg-3 col-md-6 card-container fill-y" data-search="<?php echo htmlspecialchars(strtolower($project['Title'])); ?>">
            <div id="project-<?php echo $project["ProjectID"] ?>" class="card fill-y m-2">
                <div class="card-body">
                    <h5 id="project-title" class="card-title"><?php echo $project["Title"] ?></h5>
                    
                    <p id="project-description"><?php echo $project["Description"] ?></p>
                    
                </div>
                <div class="card-footer">
                    <a id="project-link" href="display.php?page-type=project&id=<?php echo $project["ProjectID"] ?>" class="btn btn-primary">View Project</a>
                </div>
            </div>
        </div>

        <?php endforeach; ?>
        <?php elseif ($type == "projects"): ?>
            <p>No projects available.</p>
        <?php endif; ?>

        <?php if (isset($userKnowledge) && $userKnowledge): ?>
        <?php foreach ($userKnowledge as $topic): ?>
        <?php 
            $topicTags = GetTopicTags($topic["TopicID"]);
            $tagList = implode(' ', array_map(function($t) { 
                return strtolower(str_replace('/', '', $t["Name"])); 
            }, $topicTags));
        ?>
        <div class="search-item col-lg-3 col-md-6 card-container fill-y" data-search="<?php echo htmlspecialchars(strtolower($topic['Title'])); ?>" data-tags="<?php echo $tagList; ?>">
            <div id="knowledge-id" class="card fill-y m-2">
                <div class="card-body">
                    <h5 id="knowledge-title" class="card-title"><?php echo $topic["Title"] ?></h5>
                    
                    <div id="tag-container" class="d-flex flex-wrap gap-2">
                        <?php 
                        $topicTags = GetTopicTags($topic["TopicID"]);

                        foreach ($topicTags as $tag)
                        {
                            $tagText = $tag["Name"];
                            $tagClass = strtolower(str_replace('/', '', $tag["Name"]));
                            echo "<span class='badge bg-{$tagClass}'>{$tagText}</span>";
                        }
                        ?>
                    </div>
                    
                </div>
                <div class="card-footer">
                    <a id="knowledge-link" href="display.php?page-type=topic&id=<?php echo $topic["TopicID"] ?>" class="btn btn-primary">View Topic</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php elseif ($type == "knowledge"): ?>
            <p>No knowledge available.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Create: Create Knowledge/Topic/Post -->
<section id="create-knowledge">
    <!-- Knowledge Creation Modal -->
    <div class="modal fade" id="create-knowledge-modal" tabindex="-1" aria-labelledby="create-knowledge-title" style="" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered ">
        <form id="create-knowledge-form" class="modal-content shadow" style="">
        
        <!-- Modal Header-->
        <div class="modal-header">
            <h1 id="create-knowledge-title" class="modal-title fs-5">Create Knowledge Topic</h1>

            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Create Task Inputs -->
        <div class="modal-body d-flex flex-column gap-2" style="padding-bottom: 0;">
            
            <!-- Knowledge Title -->
            <input name="title" type="text" class="input-box w-100" placeholder="Post Title...">

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
                        ?>

                        <div class="form-check d-flex align-items-center gap-2">
                            <input name="users[]" class="form-check-input" type="checkbox" value="<?php echo $user['UserID']; ?>" id="user-<?php echo $user['UserID']; ?>-checkbox">
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
                        ?>
                        <div class="form-check d-flex align-items-center gap-2">
                            <input name="tags[]" class="form-check-input" type="checkbox" value="<?php echo $tag['TagID']; ?>" id="tag-<?php echo $tag['TagID']; ?>-checkbox">
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
            <button type="submit" class="btn btn-primary shadow-sm">Publish Knowledge Topic</button>
            <button type="button" class="btn btn-danger shadow-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
        </form>
        </div>
    </div>

    <!-- Knowledge Post Modal -->
    <div class="modal fade" id="create-post-modal" tabindex="-1" aria-labelledby="create-post-title" style="" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content shadow" style="">
        
        <!-- Modal Header-->
        <div class="modal-header">
            <h1 id="create-post-title" class="modal-title fs-5">Create Post</h1>

            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Create post Inputs -->
        <div class="modal-body d-flex flex-column gap-2" style="padding-bottom: 0;">
            <div id="top-row" class="gap-2">
                <input type="text" class="input-box w-100" placeholder="Post Title...">
            </div>

            <textarea placeholder="Enter Text..." style="height: 103px;"></textarea>

            <input type="file">
        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <button type="button" class="btn btn-primary shadow-sm">Publish Post</button>
            <button type="button" class="btn btn-danger shadow-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
        </div>
        </div>
    </div>
</section>

<!-- Create: Create Project -->
<section id="create-project">
    <!-- Project Creation Modal -->
    <div class="modal fade" id="create-project-modal" tabindex="-1" aria-labelledby="create-project-title" style="" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered ">
            <form id="create-project-form" class="modal-content shadow" style="">
            
            <!-- Modal Header-->
                <div class="modal-header">
                    <h1 id="create-project-title" class="modal-title fs-5">Create Project</h1>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Create Task Inputs -->
                <div class="modal-body d-flex flex-column gap-2" style="padding-bottom: 0;">
                    <div id="top-row" class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <input name="title" type="text" class="input-box w-100" placeholder="Project Title...">
                        <!-- Select Team Leaders (team-leaders[]) -->
                        <section class=" btn-group">
                            <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton3" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" style="background-color: white; border-color: gray;">
                                Select Team Leaders
                            </button>
                            <div class="dropdown-menu p-3 curved-borders" aria-labelledby="dropdownMenuButton3">
                                
                                <?php 
                                    $users = GetActiveUsers();

                                    foreach ($users as $user):
                                ?>

                                <div class="form-check d-flex align-items-center gap-2">
                                    <input name="team-leaders[]" class="form-check-input" type="checkbox" value="<?php echo $user['UserID']; ?>" id="user<?php echo $user['UserID']; ?>-checkbox">
                                    <label class="form-check-label" for="user<?php echo $user['UserID']; ?>-checkbox">
                                        <?php echo $user['Forename'] . ' ' . $user['Surname']; ?>
                                    </label>
                                </div>

                                <hr class="dropdown-divider">
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <!-- Select Project Members -->
                        <section class="btn-group">
                            <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" style="background-color: white; border-color: gray;">
                                Select Project Members
                            </button>
                            <div class="dropdown-menu p-3 curved-borders" aria-labelledby="dropdownMenuButton5">
                                
                                <?php 
                                    $users = GetActiveUsers();

                                    foreach ($users as $user):
                                ?>

                                <div class="form-check d-flex align-items-center gap-2">
                                    <input name="users[]" class="form-check-input" type="checkbox" value="<?php echo $user['UserID']; ?>" id="user<?php echo $user['UserID']; ?>-checkbox">
                                    <label class="form-check-label" for="user<?php echo $user['UserID']; ?>-checkbox">
                                        <?php echo $user['Forename'] . ' ' . $user['Surname']; ?>
                                    </label>
                                </div>

                                <hr class="dropdown-divider">
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>

                        <!-- Select Tags -->
                    <div id="middle-row" class="d-flex justify-content-center mb-2">
                        <section class="btn-group">
                            <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton4" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" style="background-color: white; border-color: gray;">
                                Select Tags
                            </button>
                            <div class="dropdown-menu p-3 curved-borders" aria-labelledby="dropdownMenuButton4">

                                <?php 
                                    $tags = GetTags();

                                    foreach ($tags as $tag):
                                ?>
                                <div class="form-check d-flex align-items-center gap-2">
                                    <input name="tags[]" class="form-check-input" type="checkbox" value="<?php echo $tag['TagID']; ?>" id="tag-<?php echo $tag['TagID']; ?>-checkbox">
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

                <textarea name="description" class="m-2" placeholder="Enter Description..." style="height: 103px;"></textarea>

                <div id="bottom-row" class="d-flex flex-row align-items-center justify-content-end p-2">
                    <label for="deadline" class="form-label me-2 mb-0">Deadline:</label>
                    <input type="date" class="form-control" id="deadline" name="deadline" style="width: 180px;">
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary shadow-sm">Create Project</button>
                    <button type="button" class="btn btn-danger shadow-sm" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const teamLeaderCheckboxes = document.querySelectorAll('input[name="team-leaders[]"]');
    const userCheckboxes = document.querySelectorAll('input[name="users[]"]');

    function updateExclusivity() {
        // Get all selected team leader IDs
        const selectedLeaders = Array.from(teamLeaderCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        // Disable matching users in "users[]" checkboxes
        userCheckboxes.forEach(cb => {
            if (selectedLeaders.includes(cb.value)) {
                cb.checked = false; // uncheck if previously checked
                cb.disabled = true;
                cb.parentElement.classList.add("text-muted"); // optional visual cue
            } else {
                cb.disabled = false;
                cb.parentElement.classList.remove("text-muted");
            }
        });

        // Same logic in reverse: selected users cannot be team leaders
        const selectedUsers = Array.from(userCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        teamLeaderCheckboxes.forEach(cb => {
            if (selectedUsers.includes(cb.value)) {
                cb.checked = false;
                cb.disabled = true;
                cb.parentElement.classList.add("text-muted");
            } else {
                cb.disabled = false;
                cb.parentElement.classList.remove("text-muted");
            }
        });
    }

    // Attach change events
    teamLeaderCheckboxes.forEach(cb => cb.addEventListener("change", updateExclusivity));
    userCheckboxes.forEach(cb => cb.addEventListener("change", updateExclusivity));

});


document.getElementById("create-project-form")
.addEventListener("submit", function (e) {

    e.preventDefault();

    const form = e.target;

    const titleInput = form.querySelector('input[name="title"]');
    const descriptionInput = form.querySelector('textarea[name="description"]');
    const deadlineInput = form.querySelector('input[name="deadline"]');
    const teamLeaderCheckboxes = form.querySelectorAll('input[name="team-leaders[]"]');
    const teamLeaderButton = document.getElementById("dropdownMenuButton3");

    let missingFields = [];

    // Remove previous error styling
    titleInput.classList.remove("input-error");
    descriptionInput.classList.remove("input-error");
    deadlineInput.classList.remove("input-error");
    teamLeaderButton.classList.remove("input-error");

    // Validate Title
    if (!titleInput.value.trim()) {
        missingFields.push("Project Title");
        titleInput.classList.add("input-error");
    }

    // Validate Description
    if (!descriptionInput.value.trim()) {
        missingFields.push("Project Description");
        descriptionInput.classList.add("input-error");
    }

    // Validate Deadline
    if (!deadlineInput.value.trim()) {
        missingFields.push("Deadline");
        deadlineInput.classList.add("input-error");
    }

    // Validate At Least One Team Leader
    const atLeastOneLeaderSelected =
        Array.from(teamLeaderCheckboxes).some(cb => cb.checked);

    if (!atLeastOneLeaderSelected) {
        missingFields.push("Team Leader");
        teamLeaderButton.classList.add("input-error");
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

    // Submit form
    const formData = new FormData(form);
    formData.append("action", "project");

    fetch("create.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then((data) => {

        if (data.success) {

            const modal = document.getElementById("create-project-modal");
            const modalInstance = bootstrap.Modal.getInstance(modal);
            modalInstance.hide();

            location.reload();

        } else {
            alert("Failed to create project: " + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert("An unexpected error occurred. Please try again.");
    });

});

document.getElementById("create-knowledge-form")
.addEventListener("submit", function (e) {

    e.preventDefault();

    const form = e.target;

    // Elements
    const titleInput = form.querySelector('input[name="title"]');
    const accessCheckboxes = form.querySelectorAll('input[name="users[]"]');
    const accessButton = document.getElementById("dropdownMenuButton1"); // Modify Access button

    let missingFields = [];

    // Remove previous error styles
    titleInput.classList.remove("input-error");
    accessButton.classList.remove("input-error");

    // Validate Title
    if (!titleInput.value.trim()) {
        missingFields.push("Title");
        titleInput.classList.add("input-error");
    }

    // Validate At Least One User Selected
    const atLeastOneUserSelected = Array.from(accessCheckboxes).some(cb => cb.checked);
    if (!atLeastOneUserSelected) {
        missingFields.push("Modify Access");
        accessButton.classList.add("input-error");
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
    formData.append("action", 'knowledge');
    formData.append("owner", "<?php echo $_SESSION['userID']; ?>");

    // Submit form
    fetch("create.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then((data) => {
        if (data.success) {

            const modal = document.getElementById("create-knowledge-modal");
            const modalInstance = bootstrap.Modal.getInstance(modal);
            modalInstance.hide();

            location.reload();

        } else {
            alert("Failed to create knowledge topic: " + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert("An unexpected error occurred. Please try again.");
    });

});


// TODO: Security: Conditioannly Render Create Panels depending on user type and page type. 
//TODO: Update for user manaegement & Show Proejct Tags

</script>

<?php //include "footer.php" ?>