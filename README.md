# 🏥 MEDDEMY

> Pakistan's dedicated online medical exam preparation platform for AFNS, NCAT, and BSN aspirants.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)

---

## 📌 Overview

MEDDEMY is a full-stack web platform built specifically for Pakistani students preparing for competitive medical entrance exams. It provides structured courses, quizzes, study materials, and books — all in one place.

---

## ✨ Features

- 🎓 **Course Player** — Structured video/text-based learning modules
- 📝 **Quiz System** — MCQ-based timed quizzes with instant feedback
- 📚 **Books Store** — Curated medical books for exam preparation
- 📁 **Study Materials** — Downloadable PDFs and resources
- 📢 **Announcements** — Admin broadcasts to all enrolled students
- 🔐 **Student Auth** — Secure login with Remember Me functionality
- 🛠️ **Admin Panel** — Full control over content, students, and analytics

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP (procedural + MySQLi) |
| Database | MySQL |
| Frontend | HTML5, CSS3, JavaScript |
| Hosting | Shared hosting (`httpdocs/`) |
| SEO | Google Search Console integrated |

---

## 📸 Screenshots

> Coming soon — add screenshots of dashboard, quiz, and course player here

---

## 🚀 Getting Started (Local Setup)

```bash
# 1. Clone the repo
git clone https://github.com/tanveer-ali/meddemy.git

# 2. Move to your localhost directory (XAMPP/WAMP)
cp -r meddemy/ /xampp/htdocs/meddemy

# 3. Import the database
# Open phpMyAdmin → Create DB 'meddemy' → Import meddemy.sql

# 4. Configure DB connection
# Edit config.php with your DB credentials

# 5. Visit in browser
http://localhost/meddemy
```

---

## 📁 Project Structure
meddemy/
├── admin/          # Admin panel (dashboard, manage content)
├── assets/         # CSS, JS, images
├── includes/       # DB config, header, footer, auth
├── courses/        # Course player pages
├── quiz/           # Quiz engine
├── books/          # Books store
├── materials/      # Study material section
├── meddemy.sql     # Database schema
└── index.php       # Landing page

---

## 👨‍💻 Developer

**Tanveer Ali**  
BS Artificial Intelligence — University of Lahore (CSDL)  
[![GitHub](https://img.shields.io/badge/GitHub-tanveer--ali-181717?style=flat&logo=github)](https://github.com/tanveer-ali)

---

## 📄 License

This project is proprietary. All rights reserved © 2025 Tanveer Ali.