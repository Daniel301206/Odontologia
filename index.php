<?php
// Punto de entrada del sistema: el home deriva al login si no hay sesión.
header('Location: views/home.php');
exit;
