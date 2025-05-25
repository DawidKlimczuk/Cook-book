<?php
require_once 'config.php';

// Zniszczenie sesji
session_destroy();

// Przekierowanie na stronę główną
header('Location: index.php');
exit();
?>