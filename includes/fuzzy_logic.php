<?php
// includes/fuzzy_logic.php

function calculateFuzzyScore(int $rawScore): int {
    if ($rawScore >= 85) return 95;
    if ($rawScore >= 70) return 85;
    if ($rawScore >= 55) return 70;
    if ($rawScore >= 40) return 55;
    return 40;
}
