<?php

declare(strict_types=1);

require_once __DIR__ . '/db_central.php';

function dbVentas(): PDO
{
    return dbCentral();
}
