<?php 
include 'Includes/dbcon.php';
session_start();
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Student Attendance Management System Login">
    <meta name="author" content="Antigravity">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <title>SAMS - Login</title>
    
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #8b5cf6; /* Violet Start */
            --primary-gradient: linear-gradient(135deg, #a855f7 0%, #8b5cf6 100%);
            --primary-hover: #7c3aed;
            --bg-body: #FEF0B3; 
            --text-main: #000000;
            --text-secondary: #4b5563;
            --card-border: #fde68a;
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
        }

        .bg-gradient-login {
            background: var(--bg-body);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container-login {
            width: 100%;
            max-width: 440px;
        }

        .glass-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .brand-text {
            color: var(--text-main);
            font-weight: 800;
            font-size: 1.6rem;
            letter-spacing: -1px;
            margin-bottom: 10px;
        }

        .logo-container {
            margin-bottom: 25px;
            display: flex;
            justify-content: center;
        }

        .logo-container img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border-radius: 16px;
            padding: 8px;
            background: var(--bg-body);
            border: 1px solid var(--card-border);
            transition: all 0.3s ease;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            background: #fff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-main);
            padding: 12px 16px;
            height: auto;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(234, 46, 46, 0.1);
            background: #fff;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(139, 92, 246, 0.3);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
            cursor: pointer;
        }

        .btn-login {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            color: white;
            padding: 14px;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 10px;
            box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.25);
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            opacity: 0;
            z-index: -1;
            transition: opacity 0.4s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(139, 92, 246, 0.3);
        }

        .btn-login:hover::before {
            opacity: 1;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            position: fixed;
            top: 25px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            background: #ef4444;
            color: #fff;
            border-radius: 12px;
            padding: 14px 28px;
            font-size: 0.9rem;
            font-weight: 700;
            z-index: 10000;
            box-shadow: 0 20px 25px -5px rgba(239, 68, 68, 0.3);
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            pointer-events: none;
        }

        .alert.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
    </style>
</head>

<body class="bg-gradient-login">
    <?php
    if(isset($_POST['login'])){
        $userType = $_POST['userType'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $password = md5($password);

        $errorMsg = "";

        if($userType == "Student"){
            header('Location: Student/login.php');
            exit;
        }

        if($userType == "Administrator"){
            $query = "SELECT * FROM tbladmin WHERE emailAddress = '$username' AND password = '$password'";
            $rs = $conn->query($query);
            $num = $rs->num_rows;
            $rows = $rs->fetch_assoc();

            if($num > 0){
                $_SESSION['userId'] = $rows['Id'];
                $_SESSION['firstName'] = $rows['firstName'];
                $_SESSION['lastName'] = $rows['lastName'];
                $_SESSION['emailAddress'] = $rows['emailAddress'];
                session_write_close();
                header('Location: Admin/index.php');
                exit;
            } else {
                $errorMsg = "Invalid Email or Password!";
            }
        } else if($userType == "ClassTeacher"){
            $query = "SELECT * FROM tblclassteacher WHERE emailAddress = '$username' AND password = '$password'";
            $rs = $conn->query($query);
            $num = $rs->num_rows;
            $rows = $rs->fetch_assoc();

            if($num > 0){
                $_SESSION['userId'] = $rows['Id'];
                $_SESSION['firstName'] = $rows['firstName'];
                $_SESSION['lastName'] = $rows['lastName'];
                $_SESSION['emailAddress'] = $rows['emailAddress'];
                $_SESSION['classId'] = $rows['classId'];
                $_SESSION['classArmId'] = $rows['classArmId'];
                session_write_close();
                header('Location: ClassTeacher/index.php');
                exit;
            } else {
                $errorMsg = "Invalid Email or Password!";
            }
        }

        if(!empty($errorMsg)){
            echo "<div id='toastAlert' class='alert'><i class='fas fa-exclamation-circle'></i> " . $errorMsg . "</div>";
        }
    }
    ?>

    <div class="container-login">
        <div class="glass-card">
            <div class="login-form">
                <div class="text-center">
                    <div class="logo-container">
                        <i class="fas fa-user-shield fa-4x mb-3" style="color: var(--primary);"></i>
                    </div>
                    <h1 class="brand-text" style="font-size: 1.8rem; margin-bottom: 10px;">SAMS <span style="color: var(--primary);">ADMIN</span></h1>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 30px;">Attendance Management System</p>
                </div>

                <form class="user" method="Post" action="">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <select required name="userType" id="userType" class="form-control">
                            <option value="">-- Select User Role --</option>
                            <option value="Administrator">Administrator</option>
                            <option value="ClassTeacher">Class Teacher</option>
                            <option value="Student">Student</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="text" class="form-control" required name="username" id="exampleInputEmail" placeholder="e.g. name@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" required class="form-control" id="exampleInputPassword" placeholder="••••••••">
                    </div>
                    
                    <input type="submit" class="btn btn-login" value="Sign In" name="login" />
                </form>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var toast = $('#toastAlert');
            if(toast.length) {
                setTimeout(function() {
                    toast.addClass('show');
                }, 100);

                setTimeout(function() {
                    toast.removeClass('show');
                }, 5000);
            }

            var role = document.getElementById('userType');
            if (role) {
                role.addEventListener('change', function(){
                    if (role.value === 'Student') {
                        window.location.href = 'Student/login.php';
                    }
                });
            }
        });
    </script>
</body>
</html>