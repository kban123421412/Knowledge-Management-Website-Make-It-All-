-- This file will contain sql statements to insert data into the PAKMS tables


-- Account
    -- Register User/Activate Account
INSERT INTO Users VALUES (UserID, Email, Password, Forename, Surname, UserType); 

-- Projects
    -- Add Users to Project
    -- Project Tasks

INSERT INTO Projects VALUES (ProjectID, Title, Description, Status, CreatedOn, Deadline);
INSERT INTO ProjectMembers VALUES (ProjectID, UserID, Leader);
INSERT INTO Tasks VALUES (TaskID, ProjectID, OwnerID, AssigneeID, Title, Description, Status, Priority, CreatedOn, Deadline);

-- Personal Tasks

INSERT INTO Tasks VALUES (TaskID, NULL, OwnerID, AssigneeID, Title, Description, Status, Priority, CreatedOn, Deadline);

-- Topics
    -- Add User to Topic
    -- Posts/Comments
    
INSERT INTO Topics VALUES (TopicID, Title, CreatedBy);
INSERT INTO TopicMembers VALUES (TopicID, UserID);
INSERT INTO Posts VALUES (PostID, NULL, TopicID, Title, Message, File, CreatedBy, CreatedOn); -- Post
INSERT INTO Posts VALUES (PostID, ParentID, TopicID, Title, Message, File, CreatedBy, CreatedOn); -- Comment

-- Tags
    -- Add Tags to Topic
    -- Add Tags to Project

INSERT INTO Tags VALUES (TagID, Name);
INSERT INTO ProjectTags VALUES (ProjectID, TagID);
INSERT INTO TopicTags VALUES (TopicID, TagID);

    
-- Notifications
    -- Add User to Notification

INSERT INTO Notifications VALUES (NotificationID, SenderID, SendDate, Title, Message);
INSERT INTO NotificationRecipients VALUES (NotificationID, RecipientID);

INSERT INTO Users (Email, Password, Forename, Surname, UserType) 
    VALUES ('gavison.pereira@make-it-all.co.uk', 'password123', 'Gavison', 'Pereira', 'Manager');

INSERT INTO Projects (Title, Description, Status, CreatedOn, Deadline) 
    VALUES ('Project 1', 'This is the first project', 'Not Started', '2026-01-01', '2026-12-31');

INSERT INTO Tasks (ProjectID, OwnerID, AssigneeID, Title, Description, Status, Priority, CreatedOn, Deadline) 
    VALUES (0001, 0003, 0003, 'Do Laundry', 'Use detergent and conditioner', 'Not Complete', 3, '2026-01-01', '2026-03-24');