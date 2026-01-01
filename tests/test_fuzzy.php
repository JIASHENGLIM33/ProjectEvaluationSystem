<?php
require_once __DIR__ . "/../includes/fuzzy_logic.php";

echo "Input 85 => " . calculateFuzzyScore(85) . PHP_EOL;
echo "Input 55 => " . calculateFuzzyScore(55) . PHP_EOL;
echo "Input 30 => " . calculateFuzzyScore(30) . PHP_EOL;
