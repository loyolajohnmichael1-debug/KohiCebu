const LOGIN_API = "../api/login.php";

const loginForm = document.getElementById("loginForm");
const loginButton = document.getElementById("loginButton");
const loginMessage = document.getElementById("loginMessage");


function showMessage(message, type = "error") {

    loginMessage.hidden = false;
    loginMessage.textContent = message;

    loginMessage.className =
        "login-message " + type;
}


function hideMessage() {

    loginMessage.hidden = true;
    loginMessage.textContent = "";

}


function saveAdminSession(data) {

    const user = data.user;

    sessionStorage.setItem(
        "token",
        data.token
    );

    sessionStorage.setItem(
        "token_expiry",
        data.expires_at
    );

    sessionStorage.setItem(
        "user_id",
        user.user_id
    );

    sessionStorage.setItem(
        "full_name",
        user.full_name
    );

    sessionStorage.setItem(
        "email",
        user.email
    );

    sessionStorage.setItem(
        "phone",
        user.phone || ""
    );

    sessionStorage.setItem(
        "role",
        user.role
    );

    sessionStorage.setItem(
        "last_activity",
        Date.now()
    );
}


loginForm.addEventListener(
    "submit",
    async function (event) {

        event.preventDefault();

        hideMessage();

        const email =
            document.getElementById("email")
                .value
                .trim();

        const password =
            document.getElementById("password")
                .value;

        if (!email || !password) {

            showMessage(
                "Please enter your email and password."
            );

            return;
        }


        loginButton.disabled = true;
        loginButton.textContent = "SIGNING IN...";


        try {

            const response =
                await fetch(
                    LOGIN_API,
                    {
                        method: "POST",

                        headers: {
                            "Content-Type":
                                "application/json"
                        },

                        body: JSON.stringify({
                            email: email,
                            password: password
                        })
                    }
                );


            const data =
                await response.json();


            if (!response.ok || !data.success) {

                showMessage(
                    data.message ||
                    "Invalid email or password."
                );

                return;
            }


            /*
             * ADMIN-ONLY SECURITY CHECK
             */

            if (data.user.role !== "ADMIN") {

                showMessage(
                    "This account does not have Admin Portal access."
                );

                return;
            }


            saveAdminSession(data);


            /*
             * Go to Admin Dashboard
             */

            window.location.href =
                "dashboard.php";

        }

        catch (error) {

            console.error(
                "Admin login error:",
                error
            );

            showMessage(
                "Unable to connect to the server. Please try again."
            );

        }

        finally {

            loginButton.disabled = false;
            loginButton.textContent = "SIGN IN";

        }

    }
);