<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require './vendor/autoload.php';

function clean($string) {
    return htmlentities($string);
}

function redirect($location) {
    return header("Location: {$location}");
}

function set_message($message) {
    if (!empty($message)) {
        $_SESSION['message'] = $message;
    }
}

function display_message() {
    if (isset($_SESSION['message'])) {
        echo $_SESSION['message'];
        unset($_SESSION['message']);
    }
}

function token_generator() {
    $token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));
    return $token;
}

function validation_errors($error_message) {
    $message = '<div class="alert alert-danger alert-dismissable">
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                <strong>Warning! </strong>' . $error_message .
            '</div>';

    return $message;
}

function username_exists($username) {
    $result = query('SELECT * FROM `users` WHERE `user_name` ="' . escape($username) .'"');
    confirm($result);
    $num = row_count($result);

    if ($num == 1) {
        return true;
    } else {
        return false;
    }
}

function email_exists($email) {
    $result = query('SELECT * FROM `users` WHERE `email` ="' . escape($email) .'"');
    confirm($result);
    $num = row_count($result);

    if ($num == 1) {
        return true;
    } else {
        return false;
    }
}

function send_mail($email, $subject, $msg, $headers) {
    // if (mail($email, $subject, $msg, $headers)) {
    //     return true;
    // }
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host       = 'smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = '9a7ef29288d870';
        $mail->Password   = '6d95b0cf1b8c96';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 25;

        //Recipients
        $mail->setFrom($headers, 'Mailer');
        $mail->addAddress($email);


        // Content
        $mail->isHTML(true); 
        $mail->Subject = $subject;
        $mail->Body    = $msg;
        $mail->AltBody = $msg;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

/******** user validation functions *********/

function validate_user_registration() {
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $first_name = clean($_POST['first_name']);
        $last_name = clean($_POST['last_name']);
        $username = clean($_POST['username']);
        $email = clean($_POST['email']);
        $password = clean($_POST['password']);
        $confirm_password = clean($_POST['confirm_password']);

        $errors = [];
        $min = 3;
        $max = 30;

        if (empty($first_name)) {
            $errors[] = "First name cannot be empty.";
        } else if (strlen($first_name) < $min) {
            $errors[] = "First name cannot be smaller than $min character.";
        } elseif (strlen($first_name) > $max) {
            $errors[] = "First name cannot be greater than $max character.";
        }

        if (empty($last_name)) {
            $errors[] = "Last name cannot be empty.";
        } else if (strlen($last_name) < $min) {
            $errors[] = "Last name cannot be smaller than $min character.";
        } elseif (strlen($last_name) > $max) {
            $errors[] = "Last name cannot be greater than $max character.";
        }

        if (empty($username)) {
            $errors[] = "Username cannot be empty.";
        } else if (strlen($username) < $min) {
            $errors[] = "Username cannot be smaller than $min character.";
        } elseif (strlen($username) > $max) {
            $errors[] = "Username cannot be greater than $max character.";
        } elseif (username_exists($username)) {
            $errors[] = "Username already exists.";
        }

        if (email_exists($email)) {
            $errors[] = "Email already exists.";
        }

        if (strlen($password) < 6) {
            $errors[] = "Password must be atleast 6 character.";
        }

        if ($password != $confirm_password) {
            $errors[] = "Password and confirm password do not match.";
        }

        if (!empty($errors)) {
            foreach($errors as $err) {
                echo $error_message = validation_errors($err);
            }
        } else {
           if (user_register($first_name, $last_name, $username, $email, $password)) {
               set_message('<div class="alert alert-success">Please check your email or spam folder for activate account</div>');

               redirect('index.php');
           }
        }
    }
}

function user_register($first_name, $last_name, $username, $email, $password) {

    $first_name = escape($first_name);
    $last_name = escape($last_name);
    $username = escape($username);
    $email = escape($email);
    $password = escape($password);

    $password = md5($password);

    $validation = md5($username . microtime());

    $sql = 'INSERT INTO `users`(`first_name`, `last_name`, `user_name`, `email`, `password`, `validation_code`) VALUES ("' . $first_name . '","' . $last_name . '","' . $username . '","' . $email . '","' . $password . '","' . $validation . '")';
    
    $query = query($sql);
    confirm($query);

    $subject = "Activate Account";
    $msg = "Please click the link below to activate your account, 
    <a href='http://localhost/login/activate.php?email=$email&code=$validation'>http://localhost/login/activate.php?email=$email&code=$validation</a>";
    $headers = "noreply@login.com";

    if(send_mail($email, $subject, $msg, $headers))
   
    return true;
} 

/******** user activate function *********/
function activate_user() {
    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        if (isset($_GET['email'])) {
            $email = clean($_GET['email']);
            $validation_code = clean($_GET['code']);

            $sql = 'SELECT id FROM users WHERE email = "' . escape($email) . '" AND validation_code = "' . escape($validation_code) . '"';
            $result = query($sql);
            confirm($result);

            if (row_count($result) == 1) {
                $sql2 = 'UPDATE `users` SET active = 1, validation_code = 0 WHERE email = "' . escape($email) . '" AND validation_code = "' . escape($validation_code) . '"';
                $result2 = query($sql2);
                confirm($result2);

                set_message ('<div class="alert alert-success">Your account has been activated.</div>');
                redirect('login.php');
            } else {
                set_message ('<div class="alert alert-danger">Your account has not been activated.</div>');
                redirect('login.php');
            }
        }
    }
}

/******** user login function *********/
function user_login() {
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $email = clean($_POST['email']);
        $password = clean($_POST['password']);
        $remember = isset($_POST['remember']);

        $errors = [];

        if (empty($email)) {
            $errors[] = "Email cannot be empty";
        }

        if (empty($password)) {
            $errors[] = "Password cannot be empty";
        }

        if (!empty($errors)) {
            foreach($errors as $err) {
                echo $error_message = validation_errors($err);
            }
        } else {
            if (user_login_confirm($email, $password, $remember)) {
                redirect('admin.php');
            } else {
                echo '<div class="alert alert-danger">Your credential is\'t correct</div>';
            }
        }
    }
}

function user_login_confirm($email, $password, $remember) {
    $sql = 'SELECT password, id FROM users WHERE email = "' . escape($email) . '" AND active = 1';
    $result = query($sql);

    if (row_count($result) == 1) {
        $row = fetch_array($result);
        $db_password = $row['password'];

        if (md5($password) == $db_password) {
            $_SESSION['email'] = $email;

            if ($remember) {
                setcookie('email', $email, time() + 86400);
            }

            return true;
        } else {
            return false;
        }

    } else {
        return false;
    }
}

function is_login() {
    if (isset($_SESSION['email']) || isset($_COOKIE['email'])) {
        return true;
    } else {
        return false;
    }
}

/******** password reset function *********/
function password_reset() {
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if (isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']) {
            $email = clean($_POST['email']);

            if (email_exists($email)) {
                $validation_code = md5($email . microtime());

                setcookie('temp_access_code', $validation_code, time() + 300);

                $sql = "UPDATE users SET validation_code = '" . escape($validation_code) . "' WHERE email = '" . escape($email) . "' AND active = 1";
                $result = query($sql);
                confirm($result);

                $subject = "Please reset your password.";
                $message = "Here is your password rest code {$validation_code}
                Or <a href='http://localhost/login/code.php?email=$email&code=$validation_code
                '>Click here</a> to reset your password. ";
                $headers = "noreply@login.com";

                if (!send_mail($email, $subject, $message, $headers)) {
                    echo validation_errors("Email could not be sent");
                } else {
                    set_message('<div class="alert alert-success">Please check your email or span box for reset code and link</div>');
                    redirect('index.php');
                }
            } else {
                echo '<div class="alert alert-danger">Email isn\'t exists.</div>';
            }
        }
    }
}

function validate_reset_code() {
    if (isset($_COOKIE['temp_access_code'])) {
        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            
            if (!isset($_GET['email']) || !isset($_GET['code'])) {
                redirect('index.php');
                
            } elseif (empty($_GET['email']) || empty($_GET['code'])) {
                redirect('index.php');
            } elseif(!empty($_GET['email']) && !empty($_GET['code'])) {
                
                
            }
        }
    } else {
        echo '<div class="alert alert-danger">Your reset session is expired.</div>';
    }
}

function check_validate_code() {
    if (isset($_COOKIE['temp_access_code'])) {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            if (isset($_POST['code']) && $_POST['code'] === $_GET['code']) {
                $email = clean($_GET['email']);
                $code = clean($_GET['code']);
                
                $sql = "SELECT id FROM users WHERE email = '" . escape($email) ."' AND validation_code = '". escape($code) . "'";
                $result = query($sql);
    
                if (row_count($result) == 1) {
                    setcookie('temp_access_code', $code, time() + 300);
    
                    redirect("reset.php?email=$email&code=$code");
                } else {
                    echo validation_errors("Code does not match.");
                }
            } else {
                echo '<div class="alert alert-danger">Wrong code!</div>';
            }
        }
    } else {
        echo '<div class="alert alert-danger">Your reset session is expired.</div>';
    }
    
}

function password_reset_option() {
    if (isset($_COOKIE['temp_access_code'])) {
        if (isset($_SESSION['token']) && isset($_POST['token']) && $_POST['token'] === $_SESSION['token']) {
            if (isset($_GET['email']) && isset($_GET['code'])) {
                if (strlen($_POST['password']) < 6) {
                    set_message('<div class="alert alert-danger">Password is less than 6 character.</div>');
                } elseif(strlen($_POST['confirm_password']) < 6) {
                    set_message('<div class="alert alert-danger">Confirm password is less than 6 character.</div>');
                } elseif ($_POST['password'] !== $_POST['confirm_password']) {
                    set_message('<div class="alert alert-danger">Password does not match.</div>');
                } else {
                    $password = md5($_POST['password']);
                    $email = $_GET['email'];
                    $sql = "UPDATE `users` SET `password`= '$password', `validation_code`= '0' WHERE `email`= '$email'";
                    $result = query($sql);
                    confirm($result);

                    set_message('<div class="alert alert-success">Your password is updated, now you can login.</div>');
                    //echo $_SESSION['message'];
                    redirect('login.php');
                }
            }
        }
    } else {
        set_message('<div class="alert alert-danger">Your reset session is expired.</div>');
        redirect('recover.php');        
    }
}

                
?>