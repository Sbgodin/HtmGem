<?php

require_once "lib-htmgem.php";

# The url argument is always absolute compared to the document root.
$url = @$_REQUEST["url"];
$urlRewriting = @$_REQUEST["rw"]=="1";

/* Installation page
 *
 * Accessing directly /htmgem will make display the self-hosted documentation
 * contained in "index.gmi". If it's removed, display an empty page with a
 * comment
 */
if (empty($url)) {
    if (!file_exists("index.gmi")) {
        http_response_code(403);
        die("<!-- index.gmi missing -->");
    }
    $t = new \htmgem\GemTextTranslate_html(@file_get_contents("index.gmi"), true, "/htmgem");
    echo $t->getFullHtml();
    exit();
}

$documentRoot = $_SERVER['DOCUMENT_ROOT'];

if (!preg_match("/\.gmi$/", $url)) {
    if ($url[-1] == "/")
        $url = $url."index.gmi";
    else
        $url = $url."/index.gmi";
}

# Removes the headling and trailling slashes, to be sure there's not any.
$filePath = rtrim($_SERVER['DOCUMENT_ROOT'], "/")."/".ltrim($url, "/");

switch(true) {
    case !realPath($filePath):
    case strpos($filePath, $documentRoot)!==0: # not in web directory
        $go404 = true;
        // Says 404 even if the file exists to not give any information.
        break;
    default:
        $go404 = false;
}

/* 404 page
 */
if ($go404) {
    error_log("HtmGem: 404 $url $filePath");
    http_response_code(404);
    $page404 = <<<EOF
# ⚠ Page non trouvée

​**$url**

=> .. 🔄 🔄
EOF;
    $t = new \htmgem\GemTextTranslate_html($page404);
    echo $t->getFullHtml();
    exit();
}

# to false only if textDecoration=0 in the URL
$textDecoration = "0" != @$_REQUEST['textDecoration'];

$fileContents = @file_get_contents($filePath);
# Removes the Byte Order Mark
$fileContents = preg_replace("/\xEF\xBB\xBF/", "", $fileContents);


/* CSS and special style management
 */

$style = @$_REQUEST['style'];
if ("source" == $style) {
    $basename = basename($filePath);
    header("Cache-Control: public");
    header("Content-Disposition: attachment; filename=$basename");
    header("Content-Type: text/plain");
    header("Content-Transfer-Encoding: binary");
    header('Content-Length: ' . filesize($filePath));
    echo $fileContents;
    exit();
} elseif ("pre" == $style) {
    # Gets the page title: the first occurrence with # at the line start
    mb_ereg("#\s*([^\n]+)\n", $fileContents, $matches);
    $page_title = @$matches[1];
    $fileContents = htmlspecialchars($fileContents, ENT_HTML5|ENT_QUOTES, "UTF-8", true);
    echo <<<EOL
<!DOCTYPE html>
<html>
<head>
<title>$page_title</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<pre>
$fileContents</pre>
</body>
</html>
EOL;
    exit();
}

if ($urlRewriting)
    $baseUrl = null;
else
    $baseUrl = dirname($url);
$t = new \htmgem\GemTextTranslate_html($fileContents, $textDecoration, $baseUrl);
if ("none" == $style) {
    $t->addCss("");
} elseif ("/" == @$style[0]) {
    $t->addCss($style);
} elseif (empty($style)) {
    $parts = pathinfo($filePath);
    $localCss = $parts["filename"].".css";
    $localCssFilePath = $parts["dirname"]."/".$localCss;
    if (file_exists($localCssFilePath)) {
        # Warning, using htmhem.php?url=… will make $localCss not found
        # as the path is relative to htmgem.php and not / !
        $t->addCss($localCss);
    }
} else { #TODO: regex check for $style
    $t->addCss("/htmgem/css/$style.css");
}

echo $t->getFullHtml();

?>
