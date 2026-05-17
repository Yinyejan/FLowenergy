<?php
// api/cerrar.php — Cierre de sesión
session_start();
session_destroy();
header('Location: ../views/index.php?ver=acceder');
exit();
