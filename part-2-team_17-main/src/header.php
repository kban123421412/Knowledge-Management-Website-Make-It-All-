
<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width"/>
    <meta name="description" content="Internal Productivity and Management system for employeews as part of Make-It-All.">

    <title>Make It All - Dashboard</title>

    <!-- Icon & CSS & JS-->
    <link rel="icon" type="image/x-icon" href="static/favicon.png">
    <link href="static/styles.css" rel="stylesheet">
    <script src="scripts.js"></script>
    

    <!-- Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <!-- Chart JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-annotation/0.5.7/chartjs-plugin-annotation.min.js"></script>
    
</head>

<body class="m-0" style="background-color: var(--bg-colour);">
    
<header id="navabar-wrapper" class="w-100">

    <nav class="navbar">

        <!-- Navbar Row 1, Brand & User -->
        <section class="nav-row-1 container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php" rel="noopener noreferrer">

                <span class="h4 mb-0 primary-text">Make-It-All</span>

            </a>

            <?php
                $notifications = GetUserNotifications($_SESSION['userID']);
                $notifCount = is_array($notifications) ? count($notifications) : 0;
            ?>

            <div id="user-options" class="dropdown">
                <button
                    id="btn-user-options"
                    class="btn d-flex align-items-center primary-text dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    style="min-width:200px";
                >
                    <div class="position-relative mt-1 me-3">
                        <!-- Switch icon if 0 notifications -->
                        <img 
                            src="<?php echo $notifCount > 0 ? 'static/notification.png' : 'static/notification-0.png'; ?>" 
                            alt="Notifications" 
                            style="width: 24px; height: auto;"
                        >
                        <!-- Show badge only if there are notifications -->
                        <?php if ($notifCount > 0): ?>
                        <span 
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                            style="font-size: 0.6rem;"
                        >
                            <?php echo $notifCount; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <span id="navbar-username" class="me-2"><?php echo $_SESSION['username'] ?? "User"; ?></span>
                </button>

                <ul class="dropdown-menu">
                    <li><a id="lbl-notification-count" class="dropdown-item" onclick="NotificationsFocus()">Notifications</a></li>
                    <li><a class="dropdown-item" href="dashboard.php?page-type=settings">Settings</a></li>
                    <li><a class="dropdown-item" onclick="LogOut()">Logout</a></li>
                </ul>
            </div>

            
        </section>
        
        <!-- Navbar Row 2, User Redirects-->
         
        <section class="nav-row-2 w-100 d-flex flex-wrap justify-content-between nav-buttons">
          
            <a class="btn nav-btn flex-grow-1 text-center nav-left <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'nav-active' : ''; ?>" id="btn-dashboard" href="dashboard.php">Dashboard</a>
            <a class="btn nav-btn flex-grow-1 text-center" id="btn-mytasks" onclick="MyTaskFocus()">My Tasks</a>
            <a class="btn nav-btn flex-grow-1 text-center <?php echo basename($_SERVER['PHP_SELF']) == 'search.php' && $_GET['type'] == 'projects' ? 'nav-active' : ''; ?>" id="btn-projects" href="search.php?type=projects">Projects</a>
            <a class="btn nav-btn flex-grow-1 text-center nav-right <?php echo basename($_SERVER['PHP_SELF']) == 'search.php' && $_GET['type'] == 'knowledge' ? 'nav-active' : ''; ?>" id="btn-knowledge" href="search.php?type=knowledge">Knowledge</a>

        </section>

    </nav>
</header>

<script>
    
    function MyTaskFocus()
    {
        const currentPage = window.location.pathname.split('/').pop();
        const queryParams = new URLSearchParams(window.location.search);
        const pageType = queryParams.get('page-type');

        if (currentPage !== "dashboard.php" || pageType === "settings") {
            window.location.replace("dashboard.php");
        } else {
            const personalTasksElement = document.getElementById("personal-tasks");
            if (personalTasksElement) {
                personalTasksElement.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        }
    }

    function NotificationsFocus()
    {
        const currentPage = window.location.pathname.split('/').pop();
        const queryParams = new URLSearchParams(window.location.search);
        const pageType = queryParams.get('page-type');

        if (currentPage !== "dashboard.php" || pageType === "settings") {
            window.location.replace("dashboard.php");
        } else {
            const notificationsSection = document.getElementById("notifications-section");
            if (notificationsSection) {
                notificationsSection.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        } 
    }

    function LogOut()
    {
        const formData = new FormData();
        formData.append('action', 'logout');
        
        fetch('login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Successfully Logged Out.');
                
                window.location.href = 'dashboard.php';
            } else {
                alert('Logout Failed: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    

</script>