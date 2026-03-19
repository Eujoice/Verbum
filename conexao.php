<?php
require 'vendor/autoload.php'; // Carrega a biblioteca | não está sendo utilizado no momento pq vendor deu problema

use Kreait\Firebase\Factory;

$factory = (new Factory)
    ->withServiceAccount('caminho/para/seu-arquivo-de-chave.json');

$firestore = $factory->createFirestore()->database();
?>