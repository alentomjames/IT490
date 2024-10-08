// Validate Signup Form
function validateSignupForm() {
    const userId = document.forms["signupForm"]["username"].value;
    const password = document.forms["signupForm"]["password"].value;
    const confirmPassword = document.forms["signupForm"]["passwordConfirm"].value;

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
    const userId = document.forms["loginForm"]["username"].value;
    const password = document.forms["loginForm"]["password"].value;

    if (userId === "" || password === "") {
        alert("All fields must be filled out.");
        return false;
    }

    return true;
}
