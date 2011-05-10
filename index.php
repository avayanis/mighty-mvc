<?php

# Load framework
require __DIR__ . "/application/libraries/mightymvc/core.php";

# Serve request
MM::serve(__DIR__, 'dev');