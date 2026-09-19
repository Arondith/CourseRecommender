# CourseMatch

CourseMatch is a PHP/MySQL web application designed to help students explore **NDMU-Marbel college programs through a 42-question college-affinity assessment**. The system combines student authentication, an interactive assessment, college-affinity scoring, program recommendations, password recovery, assessment history, and an administrative dashboard in one multi-role web application.

## Highlights

- **42-question college-affinity assessment** using a 1–5 agreement scale
- Questions are presented around NDMU-Marbel academic and career areas such as health sciences, business, education, arts and sciences, and engineering-related programs
- Visual question progress tracker with current-question and completion indicators
- Personalized **College Affinity** report after assessment completion
- Radar-chart visualization of the student's strongest college areas
- Ranked NDMU-Marbel program recommendations with compatibility scores and match labels
- Student registration, login, logout, password reset, and account recovery
- Assessment history and student records stored through the PHP/MySQL backend
- Separate administrator authentication and role-based administration tools
- Responsive student interface for assessment and recommendation viewing


## Project preview

### Login

![CourseMatch Login](images/loginCoursematch.png)

The student login connects to the PHP authentication flow, verifies the user's email and hashed password, creates a server-side session, and redirects authenticated students to the assessment dashboard. The interface also provides access to registration, password recovery, and a separate administrator sign-in.

### Assessment

![CourseMatch Assessment](images/project-preview-assessment.jpg)

The assessment contains **42 questions** answered through a **1–5 Likert scale**, from *Strongly Disagree* to *Strongly Agree*. Each question is associated with an NDMU-Marbel academic or career area—for example **Health Sciences** for Nursing, Medical Technology, and Criminology, or **Engineering, Architecture & Computing** for related technical programs. The interface shows the current question, completion count, progress bar, and a dot-by-dot progress tracker while the student works through the assessment.

### Career Report

![CourseMatch Career Report](images/project-preview-results.jpg)

After the assessment, CourseMatch generates a **College Affinity** report that summarizes the student's strongest academic areas. A radar chart visualizes affinity across college groupings such as **Arts & Sciences, Business, Education, Engineering / Architecture / Computing, and Health Sciences**. The system then presents recommended NDMU-Marbel degree programs with a **compatibility score**, progress bar, and match label such as *Best Match* or *Good Match*. These results are intended to support course exploration rather than guarantee admission or career outcomes.

## Technology

- HTML5 and CSS3
- Vanilla JavaScript
- PHP
- MySQL / MariaDB
- Chart.js
- PHPMailer

## Project structure

```text
CourseRecommender/
├── index.html              # Student sign in
├── register.html           # Student registration
├── dashboard.html          # 42-question college-affinity assessment
├── result.html             # College-affinity report and recommendations
├── admin.html              # Administrator dashboard
├── script.js               # Shared auth/API helpers
├── assessment.js           # Assessment state and draft persistence
├── recommender.js          # Recommendation scoring logic
├── results.js              # Results rendering
├── modern.css              # Modern student UI layer
├── student_api.php         # Authenticated student session/attempt API
├── admin_api.php           # Role-protected administration API
├── session_bootstrap.php   # Shared secure session configuration
├── config.php              # Environment configuration loader
├── db_connect.php          # Centralized database connection
├── send-reset.php          # Password reset email flow
├── reset-password.php      # Token validation and password update
├── database/
│   └── schema.sql          # Database schema
├── src/                    # PHPMailer source
├── .env.example            # Local configuration template
└── .gitignore
```

## Recommendation model

CourseMatch uses the student's responses to the **42-question assessment** to build a college-affinity profile. The assessment measures how strongly the student's interests align with different NDMU-Marbel academic areas and uses those accumulated affinity scores to produce the career report.

The report visualizes the student's college-area profile and ranks individual degree programs using a **compatibility score**. Higher-scoring programs are surfaced first and may receive labels such as **Best Match** or **Good Match**.

The compatibility percentage is a guidance metric produced by the application. It should be used together with program curricula, admission requirements, costs, career research, and guidance counseling rather than interpreted as an admission probability or guarantee of career success.

## Local setup

### 1. Clone the project

```bash
git clone https://github.com/Arondith/CourseRecommender.git
cd CourseRecommender
```

### 2. Configure the environment

Copy the example file:

```bash
cp .env.example .env
```

On Windows you can duplicate `.env.example` and rename the copy to `.env`.

Update the database values in `.env`. If you want password-reset emails to work, also configure the mail variables.

```env
APP_URL=http://localhost/CourseRecommender

DB_HOST=localhost
DB_PORT=3306
DB_NAME=coursematch_db
DB_USER=root
DB_PASS=

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email@example.com
MAIL_FROM_NAME=CourseMatch
```

Never commit your real `.env` file.

### 3. Create the database

Start MySQL/MariaDB through XAMPP, WAMP, or Laragon and import:

```text
database/schema.sql
```

The schema creates the required:

- `students`
- `admins`
- `student_attempts`
- `password_resets`

tables.

### 4. Run through a PHP web server

Place the repository inside your local server's web root, start Apache and MySQL, then open the project through an HTTP URL such as:

```text
http://localhost/CourseRecommender/
```

Do not open the HTML files directly with `file://`, because authentication and persistence depend on PHP sessions and API requests.

## Administrator setup

Administrator passwords must be stored as PHP password hashes, never plain text.

Generate a hash locally:

```bash
php -r "echo password_hash('CHANGE_THIS_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
```

Then insert the generated hash into the `admins` table with one of these roles:

- `superadmin`
- `moderator`
- `viewer`

Change or remove any bootstrap credentials after setup.

## Security notes

The current codebase includes several safeguards suitable for a student/portfolio project:

- Secrets are loaded from environment configuration
- `.env` is ignored by Git
- Session cookies use HttpOnly, SameSite, and strict-mode session settings
- Session IDs regenerate after successful login
- Database connection errors are logged server-side instead of exposing raw DB details
- Student assessment writes are separated from the admin API
- RIASEC scores are validated server-side before storage
- Password-reset tokens are random, stored as SHA-256 hashes, expire after one hour, and are deleted after use
- Login failures use generic credential messages

For a public production deployment, add HTTPS-only cookies, CSRF tokens, request throttling/rate limiting, dependency management through Composer, automated tests, and production-grade monitoring.

> Removing a credential from the current branch does not remove it from Git history. If a real secret was ever committed, revoke/rotate it with the provider.

## Design direction

The refreshed student experience uses a shared responsive design system across sign-in, registration, assessment, recommendations, and password recovery. The admin interface remains intentionally information-dense while the student side prioritizes clarity, progress, and mobile usability.

## Development note

CourseMatch is an educational software project. Program availability and admission requirements can change, so the recommendation data should be reviewed against the institution's current official program list before real-world deployment.
