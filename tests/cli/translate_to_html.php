<?php if (empty(@$_SERVER['SHELL']) or count($argv)<2) die();

$fileName = $argv[1];

require_once dirname(__FILE__)."/../../lib-htmgem.inc.php";

$text = file_get_contents($fileName);
$gt_html = new \htmgem\GemtextTranslate_html($text);
echo $gt_html->translatedGemtext;
