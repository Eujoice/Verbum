<?php
session_start();
session_destroy(); // Limpa todas as variáveis de login
header("Location: index.html");
exit();
?>