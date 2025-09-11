<?php
// Demo endpoint to inspect POST data from product.php form
header('Content-Type: text/plain; charset=utf-8');

echo "Dados recebidos:\n";
print_r($_POST);
