<?php
return [
  'app_name' => 'Multi Menu',
  'env' => 'local',
  'debug' => true,
  'base_url' => null, // será detectado automaticamente se vazio
  'session_name' => 'mm_session',
  'timezone' => 'America/Sao_Paulo',

  'redis' => [
    'enabled' => false,
    'host' => '127.0.0.1',
    'port' => 6379,
    'timeout' => 1.5,
    'password' => null,
    'database' => 0,
    'ttl' => 86400,
  ],

  // Quantos dias um produto aparece como "Novidade".
  // Coloque 0 para DESLIGAR completamente (seção e badge).
  'novidades_days' => 0,
];
