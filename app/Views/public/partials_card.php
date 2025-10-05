<?php

declare(strict_types=1);

// Compat partial: delega para o componente novo
// Espera $p definido no escopo
if (isset($p) && is_array($p)) {
    include __DIR__ . '/components/_card.php';
}
