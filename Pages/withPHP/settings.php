<?php
// Render the existing HTML and rewrite links to PHP counterparts for consistency
$source = __DIR__ . '/../withHTML/settings.html';
if (!is_file($source)) {
  http_response_code(500);
  echo 'Source HTML not found.';
  exit;
}
$html = file_get_contents($source);
$replacements = [
  'homemain.html' => 'homemain.php',
  'listing.html' => 'listing.php',
  'historyandtransaction.html' => 'historyandtransaction.php',
  'settings.html' => 'settings.php',
];
echo str_replace(array_keys($replacements), array_values($replacements), $html);
