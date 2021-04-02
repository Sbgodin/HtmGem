<?php if (empty(@$_SERVER['SHELL']) or count($argv)<2) die();

$fileName = $argv[1];

require_once dirname(__FILE__)."/../lib-htmgem.inc.php";

$text = file_get_contents($fileName);
$parsedGemtext = \htmgem\gemtextParser($text);
print_r(iterator_to_array($parsedGemtext));
