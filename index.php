<?php

// Load framework
require __DIR__ . "/Application/Libraries/MM/Core.php";

// Serve request
MM\Core::getInstance()->serve(__DIR__, 'dev');