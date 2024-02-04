<?php

include 'setting.php';

$connection = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);

if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}
