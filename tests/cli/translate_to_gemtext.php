<?php if (empty(@$_SERVER['SHELL']) or count($argv)<2) die();

$fileName = $argv[1];

require_once dirname(__FILE__)."/../../lib-htmgem.php";

$text = file_get_contents($fileName);
$gt_gemtext = new \htmgem\GemtextTranslate_gemtext($text);
echo strval($gt_gemtext);
