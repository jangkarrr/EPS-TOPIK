<?php
$url = 'http://localhost/EPS-TOPIK/uploads/flashcards/6ab0df19e6b33_1789976345.png';
$headers = get_headers($url);
echo "HEADERS FOR $url:\n";
print_r($headers);

$url2 = 'http://localhost/EPS-TOPIK/uploads/index.html';
$headers2 = get_headers($url2);
echo "\nHEADERS FOR $url2:\n";
print_r($headers2);
