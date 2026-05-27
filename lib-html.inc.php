<?php declare(strict_types=1);

namespace htmgem\html;

mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

define("TXT_ICON", "H͜͡m ");

function getHeader(\htmgem\GemtextTranslate_html $gt_html) {
    $css = $gt_html->getCss();
    $output = <<<EOL
<!DOCTYPE html>
<html lang="">
<head>
<title>{$gt_html->getTitle()}</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
EOL;
    foreach ($css as $c) {
        $c = htmlspecialchars($c, ENT_QUOTES, 'UTF-8');
        $output .= "\n<link type='text/css' rel='StyleSheet' href=\"$c\">\n";
    }
    $output .= <<<EOL
</head>

EOL;
    return $output;
}

function array_key_last_slice($array) {
    // array_key_last() only available as of php v7.3.0
    return key(array_slice($array, -1));
}

/**
 * @param $url the full URL to display
 * @param $pageLink if not null, means no URL rewritting
 */
function getMenu(string $scheme, string $domain, string $path, string $prefix=null) {
    $links = \htmgem\split_path_links($path, $prefix);

    // Removes the last part, as it won't hold a link
    $lastLink = array_key_last_slice($links);
    if ("index.gmi"==$lastLink) {
        // removes the index page
        array_pop($links);
        $lastLink = array_key_last_slice($links);
    }
    array_pop($links);

    $links = array($domain => "$prefix/") + $links;
    $linkList = array();
    foreach ($links as $label=>$link) {
        $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $link = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        $linkList []= "<a href=\"$link\">$label</a>\n";
    }
    $lastLink = htmlspecialchars($lastLink, ENT_QUOTES, 'UTF-8');
    $linkList [] = $lastLink."\n"; // The last part holds no link
    $output = "<div class='menu-line'>\n";
    $scheme = htmlspecialchars($scheme, ENT_QUOTES, 'UTF-8');
    $output .= "<strong>$txt_icon</strong>$scheme\n";
    $output .= implode(" / ", $linkList);
    $output .= "</div>\n";
    return $output;
}

function getFooter(\htmgem\GemtextTranslate_html $gt_html) {
    return "</body>\n</html>\n";
}

function getHtmlWithMenu($gt_html, $scheme, $domain, $path, $prefix=null) {
    $menu = getMenu($scheme, $domain, $path, $prefix);
    echo getHeader($gt_html);
    echo "<body>\n";
    echo "<div class='menu'>\n";
    echo $menu;
    echo "<hr>\n";
    echo "</div>\n";
    echo "<div id='gmi'>\n";
    echo $gt_html->translatedGemtext;
    echo "</div>\n";
    echo "<div class='menu'>\n";
    echo "<hr>\n";
    echo $menu;
    echo "</div>\n";
    echo getFooter($gt_html);
}

function get404Gmipage($url) {
    return <<<EOF
# ⚠ $url ⚠

* **Page non trouvée**
* **Page not found**


EOF;
}

?>
