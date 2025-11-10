<?php
    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "projektityo";

    $conn = new mysqli($host, $user, $password, $database);

    if ($conn->connect_error) {
        die("Yhteys epÃ¤onnistui: " . $conn->connect_error);
    }
?>