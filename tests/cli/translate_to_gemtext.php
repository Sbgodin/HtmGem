<?php if (empty(@$_SERVER['SHELL']) or count($argv)<2) die();

$fileName = $argv[1];

require_once dirname(__FILE__)."/../../lib-htmgem.inc.php";
require_once dirname(__FILE__)."/../../lib-io.inc.php";

$text = file_get_contents($fileName);
\htmgem\io\convertToUTF8($text);
$gt_gemtext = new \htmgem\GemtextTranslate_gemtext($text);
echo strval($gt_gemtext);
