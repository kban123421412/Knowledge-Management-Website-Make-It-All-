# 📚 Make-It-All Knowledge Management System (PAKMS)

[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://www.w3.org/Style/CSS/)

> A web-based enterprise Knowledge Management System built for "Make-It-All" to facilitate internal knowledge sharing, document management, project article indexing, search, and analytical metric reporting.

---

## ✨ Key Features

* **🔐 Authentication & Access Control:** Secure user login and session management (`login.php`)[cite: 5].
* **📝 Knowledge Post Management (CRUD):** Full lifecycle management to create (`create.php`), view (`read.php`, `display.php`), edit (`update.php`), and delete (`delete.php`) knowledge articles[cite: 5].
* **🔍 Advanced Search:** Full-text and metadata search engine to discover articles and project resources quickly (`search.php`)[cite: 5].
* **📂 Attachment & File Downloads:** Document sharing capabilities with file attachment download handlers (`download.php`)[cite: 5].
* **📈 Analytics & Metrics:** Visual insight dashboards for usage statistics and article tracking (`metrics.php`)[cite: 5].
* **🗄️ Relational Database Schema:** Normalized database implementation complete with entity-relationship diagrams (ERDs) and query scripts (`database/`)[cite: 5].

---

## 📂 Repository Architecture

```text
├── database/                  # Database management & schematics
│   ├── diagrams/              # Entity-Relationship Diagrams (Full, Simple, pakmsSchema)
│   ├── create_pakms.sql       # Database schema creation script
│   ├── populate_pakms.sql     # Sample data insertion script
│   ├── query_pakms.sql        # Analytical and lookup SQL queries
│   └── write_pakms.sql        # Data mutation and update scripts
└── src/                       # Web application source code
    ├── static/                # Asset storage (CSS styles and UI icons/banners)
    │   ├── styles.css         # Core stylesheet
    │   └── *.png              # UI graphics (banner, notifications, icons)
    ├── create.php             # New article creation form
    ├── dashboard.php          # Main user overview portal
    ├── delete.php             # Article deletion logic
    ├── display.php / read.php # Knowledge article rendering views
    ├── download.php           # File attachment download controller
    ├── function.php           # Core backend helper functions & DB handlers
    ├── header.php / footer.php# UI layout templates
    ├── login.php              # User authentication handling
    ├── metrics.php            # Analytics dashboard
    ├── search.php             # Knowledge search page
    └── update.php             # Article editing view
```[cite: 5]

---

## 🛠️ Tech Stack & Requirements

| Layer | Technology |
| :--- | :--- |
| **Language** | PHP (v7.4+ / v8.x)[cite: 5] |
| **Database** | MySQL / MariaDB[cite: 5] |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+)[cite: 5] |
| **Environment** | Apache Web Server / XAMPP / WAMP / PHP Built-in Server[cite: 5] |

---

## 🚀 Quick Start Guide

### 1. Database Configuration
1. Start your local **MySQL** database server[cite: 5].
2. Import the schema and populate the default dataset:
   ```bash
   mysql -u root -p < database/create_pakms.sql
   mysql -u root -p < database/populate_pakms.sql
   ```[cite: 5]

### 2. Application Setup
1. Clone the repository into your web server's root directory (e.g., `htdocs` for XAMPP):
   ```bash
   git clone [https://github.com/kban123421412/Knowledge-Management-Website-Make-It-All.git](https://github.com/kban123421412/Knowledge-Management-Website-Make-It-All.git)
   ```[cite: 5]
2. Verify database connection credentials inside `src/function.php` or `src/header.php` to ensure they match your local MySQL settings[cite: 5].

### 3. Running the Platform
* **Via Local Web Server (XAMPP/WAMP):** Navigate to `http://localhost/part-2-team_17-main/src/login.php` in your web browser[cite: 5].
* **Via PHP CLI Built-in Server:**
  ```bash
  cd part-2-team_17-main/src
  php -S localhost:8000
