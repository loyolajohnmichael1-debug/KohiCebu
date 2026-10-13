<?php
/*
 * KohiCebu Staff Portal
 * Staff / Admin Login
 *
 * Authentication is handled by:
 * ../api/login.php
 *
 * This page provides the Staff Portal UI.
 */
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>KohiCebu — Staff Login</title>

   <link rel="stylesheet" href="css/login.css">
   <link rel="stylesheet" href="css/polish.css">

</head>

<body class="login-page">

<div class="login-wrapper">

    <div class="login-card">

        <!-- BRAND -->

        <div class="login-brand">

            <div class="login-brand-name">
                KohiCebu
            </div>

            <div class="login-brand-role">
                Staff Portal
            </div>

        </div>


        <!-- HEADER -->

        <div class="login-header">

            <h1>
                Welcome back.
            </h1>

            <p>
                Sign in to manage KohiCebu operations.
            </p>

        </div>


        <!-- LOGIN FORM -->

        <form id="loginForm">

            <div class="form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="staff@kohicebu.test"
                    autocomplete="username"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

            </div>


            <button
                type="submit"
                class="login-button"
                id="loginButton"
            >
                SIGN IN
            </button>

        </form>


        <!-- ERROR / MESSAGE -->

        <div
            id="loginMessage"
            class="login-message"
            hidden
        ></div>


        <!-- FOOTER -->

        <div class="login-footer">

            Authorized Staff and Administrators only.

        </div>

    </div>

</div>


<script src="js/staff-login.js"></script>

</body>

</html>
