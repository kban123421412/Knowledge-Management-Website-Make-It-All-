-- This will contain sql statements to read data from the PAKMS tables



-- Account
    -- Login
    SELECT Password FROM Users WHERE Email = ?;

    -- Get specific User Details
    SELECT UserID, Forename, Surname, UserType FROM Users WHERE Email = ?;

-- Dashboard?


-- Projects
    -- Get all Projects a User has access to
    SELECT * FROM Projects
    WHERE ProjectID IN (
        SELECT ProjectID 
        FROM ProjectMembers 
        WHERE UserID = ?);

        -- Get all project data of a Project
        SELECT * FROM Projects WHERE ProjectID = ?;

        -- Get Members of a Project 
        SELECT * FROM Users
        WHERE UserID IN (
            SELECT UserID 
            FROM ProjectMembers 
            WHERE ProjectID = ?);

        -- Get Non-Members of a Project (for adding new members)
        SELECT * FROM Users
        WHERE UserID NOT IN (
            SELECT UserID 
            FROM ProjectMembers 
            WHERE ProjectID = ?);

        -- Get Tasks of a Project
        SELECT * FROM Tasks WHERE ProjectID = ?;

    -- Get Project Tasks assigned to a User ??

    -- Searching subset of topics


    -- !!! Project Metrics !!!

-- Personal Tasks
    -- Get all Personal Tasks of a User
    SELECT * FROM Tasks 
    WHERE ProjectID IS NULL AND (OwnerID = ?);

-- Tags
    -- Get all Tags
    SELECT * FROM Tags

    -- Get Tags of a Topic
    SELECT * FROM Tags
    WHERE TagID IN (
        SELECT TagID FROM TopicTags WHERE TopicID = ?
    )

    -- Get Topics of a Tag (combination)
    -- Get Tags of a Project
    -- Get Projects of a Tag


-- Topics
    -- Get all Topics user has access to SHERZ
        -- Get Members of Topic  MAYBE NOT
        -- Get Tags for topic
        -- Get Posts of Topic
    -- Get Comments of a Post
    -- Searching subset of topics


-- Notifications 
    -- Get all Notifications for a User (Notifications shown should have a limit or expiry date, may require somme sort of archviing code later)
    -- Get specific Notification details
