<?php
session_start();
session_unset(); // Remove todas as variáveis da sessão
session_destroy(); // Destrói a sessão

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Location: index.php"); 
exit();
?>