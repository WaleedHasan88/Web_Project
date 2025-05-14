

# PHP University Portal

A PHP-based web application for educational institutions with dedicated dashboards for **administrators**, **teachers**, and **students**.

### ✨ Features

* User registration and login system
* Admissions and campus information pages
* Research section
* General info pages: About, Contact, Terms, Privacy
* Database setup via `epu_portal.sql`

---

### 📁 Project Structure

```
.
├── epu_portal.sql                # SQL file for database schema and initial data
└── src/
    ├── admin/                    # Admin dashboard and functionality
    ├── assets/                   # CSS, JS, images, and other static files
    ├── includes/                 # Common PHP includes (e.g., header, footer, config)
    ├── public/                   # Public-facing pages
    │   ├── about.php
    │   ├── admissions.php
    │   ├── campus.php
    │   ├── contact.php
    │   ├── forgot_password.php
    │   ├── index.php             # Landing page
    │   ├── portal.php            # Main portal access
    │   ├── privacy.php
    │   ├── process_registration.php
    │   ├── register.php
    │   ├── research.php
    │   └── terms.php
    ├── student/                  # Student dashboard and features
    ├── teacher/                  # Teacher dashboard and features
    └── uploads/                  # Uploaded files (e.g., profile pictures, documents)
```


