<?php
if (isset($_GET['senha'])) {
    $senha = $_GET['senha'];
    echo "Senha em texto: " . htmlspecialchars($senha) . "<br>";
    echo "Hash gerado: " . password_hash($senha, PASSWORD_BCRYPT);
} else {
    echo "Use assim: gera_senha.php?senha=123456";
}
