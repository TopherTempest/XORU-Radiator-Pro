```php
<?php

session_start();

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/validation.php';

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}



|--------------------------------------------------------------------------
| DEFAULT ADMIN ACCOUNT
|--------------------------------------------------------------------------
| Username: admin
| Password: admin123
|
| This only creates the account if "admin" does not already exist.
|--------------------------------------------------------------------------

try {

    $checkAdmin = $pdo->prepare(
        "SELECT id FROM admins WHERE username = ? LIMIT 1"
    );

    $checkAdmin->execute(['admin']);

    $existingAdmin = $checkAdmin->fetch();

    if (!$existingAdmin) {

        $hashedPassword = password_hash(
            'admin123',
            PASSWORD_DEFAULT
        );

        $createAdmin = $pdo->prepare(
            "INSERT INTO admins (username, password)
             VALUES (?, ?)"
        );

        $createAdmin->execute([
            'admin',
            $hashedPassword
        ]);
    }

} catch (PDOException $e) {

    $error = 'Unable to initialize admin account. Please check your database connection.';
}


/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['admin_id'])) {

    header('Location: ../admin/dashboard.php');

    exit;
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

$error = $error ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            (string) $_POST['csrf_token']
        )
    ) {

        $error = 'Invalid request. Please refresh the page and try again.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | GET USER INPUT
        |--------------------------------------------------------------------------
        */

        $username = trim(
            (string) ($_POST['username'] ?? '')
        );

        $password = (string) ($_POST['password'] ?? '');


        /*
        |--------------------------------------------------------------------------
        | BASIC VALIDATION
        |--------------------------------------------------------------------------
        */

        if ($username === '') {

            $error = 'Please enter your admin username.';

        } elseif ($password === '') {

            $error = 'Please enter your admin password.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | FIND ADMIN
            |--------------------------------------------------------------------------
            */

            try {

                $stmt = $pdo->prepare(
                    "SELECT id, username, password
                     FROM admins
                     WHERE username = ?
                     LIMIT 1"
                );

                $stmt->execute([
                    $username
                ]);

                $admin = $stmt->fetch();


                /*
                |--------------------------------------------------------------------------
                | VERIFY PASSWORD
                |--------------------------------------------------------------------------
                */

                if (
                    !$admin ||
                    !password_verify(
                        $password,
                        $admin['password']
                    )
                ) {

                    $error = 'Invalid admin username or password.';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | LOGIN SUCCESS
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(true);

                    $_SESSION['admin_id'] =
                        (int) $admin['id'];

                    $_SESSION['admin_username'] =
                        $admin['username'];


                    /*
                    |--------------------------------------------------------------------------
                    | REDIRECT
                    |--------------------------------------------------------------------------
                    */

                    header(
                        'Location: ../admin/dashboard.php'
                    );

                    exit;
                }

            } catch (PDOException $e) {

                $error =
                    'Database error while logging in. Please check your database connection.';
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login - XORU Radiator Pro</title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

</head>

<body>

<div class="admin-login-page">

    <!-- =========================================
         LEFT SIDE
         ========================================= -->

    <section class="admin-login-brand">

        <div class="admin-login-main-logo">

            <img
                src="../assets/xoru-blue.png"
                alt="XORU Radiator Pro Logo"
            >

        </div>

        <p>
            Administrator portal for managing customer
            bookings, schedules, services, and repair requests.
        </p>

    </section>


    <!-- =========================================
         RIGHT SIDE
         ========================================= -->

    <section class="admin-login-side">

        <div class="admin-login-card">

            <!-- LOGO -->

            <div class="admin-login-small-logo">

                <img
                    src="../assets/xoru-blue.png"
                    alt="XORU Radiator Pro Logo"
                >

            </div>


            <h2>
                Admin Login
            </h2>


            <p>
                Sign in to access the XORU Radiator Pro dashboard.
            </p>


            <!-- =========================================
                 ERROR MESSAGE
                 ========================================= -->

            <?php if ($error !== ''): ?>

                <div class="admin-login-error">

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- =========================================
                 LOGIN FORM
                 ========================================= -->

            <form
                method="POST"
                action=""
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >


                <label for="username">
                    Username
                </label>

                <input
                    id="username"
                    type="text"
                    name="username"
                    placeholder="Enter admin username"
                    autocomplete="username"
                    required
                >


                <label for="password">
                    Password
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Enter admin password"
                    autocomplete="current-password"
                    required
                >


                <button
                    type="submit"
                    class="login-submit"
                >
                    Login to Dashboard
                </button>

            </form>


            <!-- BACK LINK -->

            <a
                href="../index.php"
                class="admin-login-back"
            >
                ← Back to XORU website
            </a>

        </div>

    </section>

</div>


<!-- =========================================
     LOGIN PAGE ONLY CSS
     ========================================= -->

<style>

.admin-login-page {

    min-height: 100vh;

    display: grid;

    grid-template-columns: 1fr 1fr;

    align-items: center;

}


.admin-login-brand {

    text-align: center;

    padding: 40px;

}


.admin-login-main-logo {

    width: 270px;

    max-width: 80%;

    margin: 0 auto 30px;

}


.admin-login-main-logo img {

    width: 100%;

    height: auto;

    display: block;

    object-fit: contain;

}


.admin-login-brand p {

    max-width: 500px;

    margin: 0 auto;

    line-height: 1.6;

}


.admin-login-side {

    display: flex;

    justify-content: center;

    padding: 40px;

}


.admin-login-card {

    width: 100%;

    max-width: 430px;

    padding: 35px;

    border-radius: 16px;

    background: rgba(86, 89, 96, 0.95);

    box-sizing: border-box;

}


.admin-login-small-logo {

    width: 80px;

    height: 80px;

    margin: 0 auto 20px;

    display: flex;

    align-items: center;

    justify-content: center;

}


.admin-login-small-logo img {

    width: 100%;

    height: 100%;

    object-fit: contain;

    display: block;

}


.admin-login-card h2 {

    text-align: center;

    margin-bottom: 10px;

}


.admin-login-card > p {

    text-align: center;

    margin-bottom: 25px;

}


.admin-login-error {

    padding: 12px 15px;

    margin-bottom: 20px;

    border-radius: 8px;

    background: #8b1e1e;

    color: #ffffff;

    font-size: 14px;

}


.admin-login-card form {

    display: flex;

    flex-direction: column;

}


.admin-login-card label {

    margin-bottom: 7px;

    font-weight: 600;

}


.admin-login-card input {

    width: 100%;

    box-sizing: border-box;

    padding: 13px 14px;

    margin-bottom: 18px;

    border: 1px solid #6e7077;

    border-radius: 8px;

    background: #ffffff;

    color: #111111;

    font-size: 15px;

}


.login-submit {

    width: 100%;

    padding: 14px;

    margin-top: 5px;

    border: none;

    border-radius: 8px;

    background: #1265bd;

    color: #ffffff;

    font-size: 16px;

    font-weight: 700;

    cursor: pointer;

}


.login-submit:hover {

    background: #0b4f96;

}


.admin-login-back {

    display: block;

    margin-top: 22px;

    text-align: center;

    color: #ffffff;

    text-decoration: none;

}


.admin-login-back:hover {

    text-decoration: underline;

}


/* =========================================
   MOBILE
   ========================================= */

@media (max-width: 800px) {

    .admin-login-page {

        grid-template-columns: 1fr;

    }

    .admin-login-brand {

        padding-bottom: 10px;

    }

    .admin-login-main-logo {

        width: 200px;

    }

    .admin-login-side {

        padding-top: 10px;

    }

}

</style>

</body>

</html>
```
