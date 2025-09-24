# SkillForge

An interactive coding practice platform with code problems and MCQs. Users register and sign in (MySQL), solve problems and answer MCQs (MongoDB), and track progress with a modern, animated UI.

## Features
- Authentication with secure password hashing (MySQL)
- Code challenges per language with an in-browser editor (Ace)
- MCQ practice with language/difficulty filters
- Leaderboard based on total submissions
- Admin-only submissions review with code viewer
- Persistent user preferences (theme/animations)
- Delightful UI with 3D tilt and cursor spotlight effects

## Tech Stack
- PHP 8+, Apache (XAMPP)
- MySQL 8+ (users/auth)
- MongoDB 5+ (problems, mcq, submissions, user_prefs)
- MongoDB PHP extension (`php_mongodb`)
- Bootstrap 5, Ace Editor

## Project Structure
```
config/
  db_mysql.php       # MySQL connection
  db_mongo.php       # MongoDB helper
index.php            # Landing (redirects logged-in users to dashboard)
login.php            # Login
register.php         # Registration
logout.php           # Logout
profile.php          # Profile, password change, preferences
dashboard.php        # Language tiles + entry points
problems.php         # Code problem player + submissions
mcq.php              # MCQ player + submissions
submissions.php      # Admin review (requires role=admin)
leaderboard.php      # Leaderboard (aggregate from submissions)
mysql.sql            # MySQL schema seed (see note below)
```

## Requirements
- XAMPP (Apache + PHP + MySQL)
- MongoDB server running locally at `mongodb://localhost:27017`
- PHP MongoDB extension enabled
  - Windows: enable `php_mongodb.dll` in `php.ini`, restart Apache

## Quick Start
1. Clone/copy the project into your XAMPP htdocs directory:
   - `C:\\xampp\\htdocs\\skill-forge`
2. Start Apache and MySQL in XAMPP Control Panel.
3. Create the MySQL schema:
   - Import `mysql.sql` into MySQL (phpMyAdmin or CLI).
   - IMPORTANT: The app uses a column named `password_hash`.
     If your `mysql.sql` created `password` instead, run:
     ```sql
     ALTER TABLE users CHANGE COLUMN password password_hash VARCHAR(255) NOT NULL;
     ```
4. Configure database credentials in `config/db_mysql.php` as needed (do not use root/admin in production).
5. Ensure MongoDB is running locally and the PHP MongoDB extension is enabled.
6. Visit `http://localhost/skill-forge/index.php`.

## Creating an Admin User
After registering a user, promote it to admin in MySQL:
```sql
UPDATE users SET role='admin' WHERE email='your-admin-email@example.com';
```
Admins can open `submissions.php` to review all submissions.

## MongoDB Collections
- `problems`
  - Example fields: `language` ("javascript"), `title`, `description`, `starter_code`, `order`
- `mcq`
  - Example fields: `language` ("javascript"), `difficulty` ("easy"|"medium"|"hard"), `question`, `options` (array), `answer` (index)
- `submissions`
  - For code: `{ type: 'code', user_id: <int>, problem_id: <ObjectId>, language, code, submitted_at }`
  - For MCQ: `{ type: 'mcq', user_id: <int>, mcq_id: <ObjectId>, choice, correct, submitted_at }`
- `user_prefs`
  - `{ user_id: <int>, theme: 'dark'|'light', animations: 'on'|'off', updated_at }`

## Adding Content
- Code problems: insert documents into `problems` with a `language` that matches the dashboard tiles (e.g., `javascript`, `html`, `css`, `python`, `c`, `cpp`, `java`). The dashboard auto-detects languages and counts.
- MCQs: insert documents into `mcq`. The MCQ page supports optional filtering by language and difficulty.

## Security Notes
- Change MySQL credentials in `config/db_mysql.php`.
- Consider creating a non-root MySQL user with limited privileges.
- Add CSRF tokens to forms if you deploy publicly.
- Validate/sanitize all inputs if you extend endpoints.

## Troubleshooting
- "MongoDB PHP extension not enabled": enable `php_mongodb` in `php.ini`, restart Apache.
- "Failed to connect to MongoDB": ensure the MongoDB server is running at `mongodb://localhost:27017`.
- Password errors on login: confirm `users.password_hash` exists and registration created a hash.
- Empty dashboard tiles: seed `problems` for desired languages or rely on the predefined language list.

## Customization
- Update the predefined language list and display labels in `dashboard.php`.
- Tweak 3D tilt/spotlight intensities in `dashboard.php` (search for `maxTilt` and `--glow`).
- Branding (logo/name/colors) can be adjusted in each page’s `<style>` block.

## License
This project is provided as-is. Add your preferred license if you plan to distribute.


