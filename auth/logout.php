<?php
include "../db/db.php";
session_destroy();
header("Location: ../auth/login.php");
exit;
