<?php
$OPENAI_API_KEY = getenv("OPENAI_API_KEY");

if (!$OPENAI_API_KEY) {
    die("OpenAI API Key missing! Please set environment variable.");
}
?>
