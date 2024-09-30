// Validate Signup Form
function validateSignupForm() {
    const userId = document.forms["signupForm"]["user_id"].value;
    const password = document.forms["signupForm"]["user_pwd"].value;
    const confirmPassword = document.forms["signupForm"]["user_pwd_confirm"].value;

    if (userId === "" || password === "" || confirmPassword === "") {
        alert("All fields must be filled out.");
        return false;
    }

    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }

    if (password.length < 6) {
        alert("Password must be at least 6 characters long.");
        return false;
    }

    return true;
}

// Validate Login Form
function validateLoginForm() {
    const userId = document.forms["loginForm"]["user_id"].value;
    const password = document.forms["loginForm"]["user_pwd"].value;

    if (userId === "" || password === "") {
        alert("All fields must be filled out.");
        return false;
    }

    return true;
}
