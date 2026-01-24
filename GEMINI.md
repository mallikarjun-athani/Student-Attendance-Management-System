# GEMINI.md

## Project Overview

This project is a Student Attendance Management System built with PHP. It allows for managing students, teachers, classes, and their attendance records. The system has three distinct user roles, each with its own panel and functionalities:

*   **Administrator:** Can manage classes, class arms, teachers, and students. They have a complete overview of the system's data.
*   **Class Teacher:** Can view their assigned class details, take student attendance, and view attendance records.
*   **Student:** Can view their attendance information.

The application follows a procedural programming style in PHP. It uses a MySQL database for data storage and vanilla JavaScript with AJAX for some dynamic interactions. The frontend is styled using the Ruang Admin template, which is based on Bootstrap.

## Building and Running

This is a classic PHP/MySQL web application that runs on a web server stack like XAMPP, WAMP, or MAMP.

1.  **Set up the Database:**
    *   Start your Apache and MySQL services.
    *   Using a database management tool like phpMyAdmin, create a new database named `attendancemsystem`.
    *   Import the `DATABASE FILE/attendancemsystem.sql` file into the newly created database. This will create the necessary tables and populate them with some initial data.

2.  **Configure Database Connection:**
    *   Open the `Includes/dbcon.php` file.
    *   Update the database credentials (`$host`, `$user`, `$pass`, `$db`) to match your local environment if they are different from the defaults (the default database name in the file is `attendencemsystem01`, you might need to change it to `attendancemsystem` as per the SQL file).

3.  **Run the Application:**
    *   Place the entire project folder (`Student-Attendance-System01-main`) into your web server's document root (e.g., `C:/xampp/htdocs/` for XAMPP).
    *   Open your web browser and navigate to `http://localhost/Student-Attendance-System01-main/`.

4.  **Login:**
    *   You can log in using the credentials provided in the `README.md` file.
        *   **Admin:** `admin@mail.com`
        *   **Teacher:** `teacher@mail.com`
    *   *Note: Passwords are not provided in the `README.md`, but the admin password hash in the SQL file (`D00F5D5217896FB7FD601412CB890830`) is an MD5 hash of `password`.*

## Development Conventions

*   **Directory Structure:** The project is organized by user roles, with `Admin/`, `ClassTeacher/`, and `Student/` directories containing the specific logic and views for each role.
*   **Includes:** Common reusable PHP components like the database connection (`dbcon.php`), header, footer, and sidebar are located in the `Includes/` directory and included in various files.
*   **Procedural PHP:** The code is written in a procedural style. Each file typically handles a specific page or action.
*   **Database Interaction:** Database queries are written directly in the PHP files using the `mysqli` extension.
*   **AJAX:** Asynchronous JavaScript is used for dynamic features, with dedicated `ajax*.php` files in the `Admin` and `ClassTeacher` directories to handle these requests.
*   **Styling:** The UI is based on the "Ruang Admin" theme. CSS and SCSS files can be found in the respective `css/` and `scss/` folders.
