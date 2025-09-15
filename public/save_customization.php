<?php
// Demo endpoint to inspect POST data from customize.php form
header('Content-Type: text/plain; charset=utf-8');

echo "Personalização recebida:\n";
print_r($_POST);
