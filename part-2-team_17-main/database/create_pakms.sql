CREATE TABLE IF NOT EXISTS Users (
    UserID   INTEGER       PRIMARY KEY AUTO_INCREMENT,
    Email    VARCHAR (191) NOT NULL UNIQUE CHECK (Email LIKE '%_@make-it-all.co.uk'), -- size of 254 from IETF following the submitted erratum for RFC 3696.
    Password CHAR    (255) NOT NULL, -- hashed, check format on frontend
    Forename VARCHAR (100) NOT NULL, -- CHECK (LENGTH(Forename) > 0),
    Surname  VARCHAR (100) NOT NULL, -- CHECK (LENGTH(Surname) > 0),
    UserType ENUM          ('Worker', 'Manager', 'Inactive', 'Deleted') DEFAULT 'Worker'
);

CREATE TABLE IF NOT EXISTS Projects (
    ProjectID     INTEGER       PRIMARY KEY AUTO_INCREMENT,
    Title         VARCHAR (100) NOT NULL,
    Description   TEXT,
    Status        ENUM         ('Not Complete', 'Complete', 'Overdue') DEFAULT 'Not Complete',
    CreatedOn     DATE         NOT NULL, -- DEFAULT CURRENT_DATE, -- Format: 2008-11-11
    Deadline      DATE         NOT NULL  -- CHECK (Deadline >= CreatedOn)
);

CREATE TABLE IF NOT EXISTS ProjectMembers (
    ProjectID INTEGER,
    UserID    INTEGER,
    Leader    BOOLEAN       NOT NULL DEFAULT FALSE, -- If true, user is team leader for the project, else is a worker
    PRIMARY KEY (ProjectID, UserID),
    
    FOREIGN KEY (ProjectID) REFERENCES Projects (ProjectID) ON DELETE CASCADE,
    FOREIGN KEY (UserID) REFERENCES Users (UserID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Tasks (
    TaskID      INTEGER       PRIMARY KEY AUTO_INCREMENT,
    ProjectID   INTEGER,                -- If project ID null then task is personal
    OwnerID     INTEGER       NOT NULL, -- For personal tasks OwnerID == AssigneeID, for project Owner == Manager/Team Leader
    AssigneeID  INTEGER       NOT NULL, -- For project tasks Assignee would be a worker
    Title       VARCHAR (100) NOT NULL, -- CHECK (LENGTH(Title) > 0),
    Description TEXT,
    Status      ENUM('Not Complete', 'Complete', 'Overdue') DEFAULT 'Not Complete',
    Priority    TINYINT       NOT NULL, -- 1 = High, 2 = Medium, 3 = Low
    CreatedOn   DATE          NOT NULL, -- DEFAULT CURRENT_DATE, -- Format: 2006-04-24
    Deadline    DATE          NOT NULL, -- CHECK (Deadline >= CreatedOn),
    CompletedOn DATE,                   -- Date task marked complete, null if not complete
    
    FOREIGN KEY (ProjectID)  REFERENCES Projects (ProjectID) ON DELETE CASCADE,
    FOREIGN KEY (OwnerID)    REFERENCES Users (UserID), -- Task shouldn't be deleted if owner is deleted
    FOREIGN KEY (AssigneeID) REFERENCES Users (UserID)  -- Task shouldn't be deleted if assignee is deleted
);

CREATE TABLE IF NOT EXISTS Topics (
    TopicID     INTEGER       PRIMARY KEY AUTO_INCREMENT,
    Title       VARCHAR (100) NOT NULL UNIQUE, -- CHECK (LENGTH(Title) > 0),
    CreatedBy   INTEGER       NOT NULL,
    
    FOREIGN KEY (CreatedBy) REFERENCES Users (UserID) -- Topic shouldn't be deleted if user is deleted
);

CREATE TABLE IF NOT EXISTS TopicMembers (
    TopicID INTEGER,
    UserID  INTEGER, 
    PRIMARY KEY (TopicID, UserID),
    
    FOREIGN KEY (TopicID) REFERENCES Topics (TopicID) ON DELETE CASCADE,
    FOREIGN KEY (UserID)  REFERENCES Users (UserID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Posts (
    PostID      INTEGER       PRIMARY KEY AUTO_INCREMENT,
    ParentID    INTEGER,      -- comments will have ParentID of the PostID they belong to
    TopicID     INTEGER       NOT NULL,
    Title       VARCHAR (100) NOT NULL,
    Message     TEXT,
    File        VARCHAR (255),
    CreatedBy   INTEGER       NOT NULL,
    CreatedOn   DATE         NOT NULL, -- DEFAULT CURRENT_DATE, 
    
    FOREIGN KEY (ParentID) REFERENCES Posts (PostID) ON DELETE CASCADE,  -- Null ParentID = Post, else is a comment
    FOREIGN KEY (TopicID) REFERENCES Topics (TopicID) ON DELETE CASCADE, -- All related posts/comments deleted if topic deleted
    FOREIGN KEY (CreatedBy) REFERENCES Users (UserID) -- Post shouldn't be deleted if user is deleted

);




CREATE TABLE IF NOT EXISTS Tags (
    TagID INTEGER      PRIMARY KEY AUTO_INCREMENT,
    Name  VARCHAR (100) NOT NULL UNIQUE -- CHECK (LENGTH(Name) > 0)
);

CREATE TABLE IF NOT EXISTS ProjectTags (
    ProjectID INTEGER,
    TagID     INTEGER,
    PRIMARY KEY (ProjectID, TagID),
    
    FOREIGN KEY (ProjectID) REFERENCES Projects (ProjectID) ON DELETE CASCADE, 
    FOREIGN KEY (TagID) REFERENCES Tags (TagID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS TopicTags (
    TopicID INTEGER,
    TagID   INTEGER,
    PRIMARY KEY (TopicID,TagID),
    
    FOREIGN KEY (TopicID) REFERENCES Topics (TopicID) ON DELETE CASCADE,
    FOREIGN KEY (TagID)   REFERENCES Tags (TagID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Notifications (
    NotificationID INTEGER       PRIMARY KEY AUTO_INCREMENT,
    SenderID       INTEGER       NOT NULL,
    SendDate       DATE, -- DEFAULT CURRENT_DATE,
    Title          VARCHAR (100) NOT NULL, -- CHECK (LENGTH(Title) > 0),
    Message        TEXT,
    
    FOREIGN KEY (SenderID) REFERENCES Users (UserID) ON DELETE CASCADE -- if user deleted, their notifications sent out are deleted
);


CREATE TABLE IF NOT EXISTS NotificationRecipients (
    NotificationID INTEGER,
    ReceiverID     INTEGER,
    PRIMARY KEY (NotificationID, ReceiverID),
    
    FOREIGN KEY (NotificationID) REFERENCES Notifications (NotificationID) ON DELETE CASCADE,
    FOREIGN KEY (ReceiverID)     REFERENCES Users (UserID) ON DELETE CASCADE
);


-- Views

-- CREATE VIEW ArchivedProjects AS
--     SELECT *
--     FROM Projects
--     WHERE Status = 'Complete';
     

-- CREATE VIEW PersonalTasks AS
--     SELECT *
--     FROM Tasks
--     WHERE ProjectID IS NULL;
     

-- CREATE VIEW ProjectTasks AS
--     SELECT *
--     FROM Tasks
--     WHERE ProjectID IS NOT NULL;


-- CREATE VIEW PublicUserProfile AS
--     SELECT UserID,
--            Forename,
--            Surname,
--            Email,
--            UserType
--     FROM Users;
      

-- CREATE VIEW OnlyPosts AS
--     SELECT *
--     FROM Posts
--     WHERE ParentID IS NULL;