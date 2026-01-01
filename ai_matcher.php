<?php

function create_embedding(string $text): array
{
    $text = strtolower($text);

    $keywords = [
        'ai', 'machine learning', 'ml',
        'web', 'website', 'frontend', 'backend',
        'database', 'sql', 'mysql',
        'network', 'security',
        'system', 'software'
    ];

    $vector = [];

    foreach ($keywords as $word) {
        $vector[] = substr_count($text, $word);
    }

    return $vector;
}


function cosine_similarity(array $v1, array $v2): float
{
    $dot = 0;
    $normA = 0;
    $normB = 0;

    foreach ($v1 as $i => $value) {
        $dot += $value * $v2[$i];
        $normA += $value ** 2;
        $normB += $v2[$i] ** 2;
    }

    if ($normA == 0 || $normB == 0) {
        return 0;
    }

    return $dot / (sqrt($normA) * sqrt($normB));
}
