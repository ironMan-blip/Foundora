<?php
    session_start();

    if (!isset($_SESSION["user_id"])) {
        header("Location: index.html?error=login_required");
        exit();

    }
?>