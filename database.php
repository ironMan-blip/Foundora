<?php
    $db_server = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "foundora";

    $conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);


    if (!$conn) {
        die("Could not connect: " . mysqli_connect_error());
    }

    //echo "You are connected";

?>