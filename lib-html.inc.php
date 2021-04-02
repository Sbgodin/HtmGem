<?php declare(strict_types=1);

namespace htmgem\html;

mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

/**
 * Returns a full HTML page base
 */
function getFullHtml(\htmgem\GemtextTranslate_html $gt_html) {
    $css = $gt_html->getCss();
    if (!$css) $css = array("/htmgem/css/htmgem.css");
    $output = <<<EOL
<!DOCTYPE html>
<html lang="">
<head>
<title>{$gt_html->getTitle()}</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
EOL;
    foreach ($css as $c) {
        $output .= "\n<link type='text/css' rel='StyleSheet' href='$c'>\n";
    }
    $output .= <<<EOL
</head>
<body>\n
EOL;
    $output .= $gt_html->translatedGemtext;
    $output .= "</body>\n</html>\n";

    return $output;
}

function get404Gmipage($message, $url) {
    return <<<EOF
# ⚠ $message

​**$url**

=> .. 🔄 🔄
EOF;
}

?>
