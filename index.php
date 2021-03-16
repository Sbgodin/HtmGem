<?php

require_once "lib-htmgem.php";
use function htmgem\translateGemToHtml;

# to false only if textDecoration=0 in the URL
$textDecoration = "0" != @$_REQUEST['textDecoration'];

/* The url argument is always absolute compared to the document root
 * The leading slash is removed. so url=/foo/bar and url=foo/bar ar the same.
 */
$url = @$_REQUEST["url"];

######################################## Installation page
if (empty($url)) {
    if (!file_exists("index.gmi")) {
        http_response_code(403);
        die("<!-- index.gmi missing -->");
    }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<title>Installation de HtmGem</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
<?php include("css/htmgem.css"); ?>
</style>
</head>
<body>
<?php
    echo translateGemToHtml(@file_get_contents("index.gmi"));
    echo "</body>\n</html>\n";
    die();
}
######################################## /Installation page

# Removes the headling and trailling slashes, to be sure there's not any.
$filePath = rtrim($_SERVER['DOCUMENT_ROOT'], "/")."/".ltrim($url, "/");

$fileContents = @file_get_contents($filePath);


######################################## 404 page
if (empty($fileContents)) {
    error_log("HtmGem: 404 $url $filePath");
    http_response_code(404); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
<?php include("css/htmgem.css"); ?>
</style>
</head>
<body>
<?php
    $text404 = <<<EOF
# ⚠ Page non trouvée

​**$url**

=> $url Recharger 🔄

=> /
EOF;
echo translateGemToHtml($text404);
echo "</body>\n</html>";
die();
}
######################################## /404 page

# Removes the Byte Order Mark
$fileContents = preg_replace("/\xEF\xBB\xBF/", "", $fileContents);

# Gets the page title: the first occurrence with # at the line start
mb_ereg("#\s*([^\n]+)\n", $fileContents, $matches);
$page_title = @$matches[1];

###################################### CSS Management
/**
* if &style=source displays the source directly and stops.
* if there's a filename.css besides filename.gmi, use the css and stops.
* if &style=<NOTHING> then embbed the default style, and stops.
* if &style=<word not beginngin by slash> then use htmgem/word.css
* if &style=/… then use the … as as stylesheet.
**/

if ("source" == $style) {
    $basename = basename($filePath);
    header("Cache-Control: public");
    header("Content-Disposition: attachment; filename=$basename");
    header("Content-Type: text/plain");
    header("Content-Transfer-Encoding: binary");
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit();
} elseif ("pre" == $style) {
    $fileContents = htmlspecialchars($fileContents, ENT_HTML5|ENT_NOQUOTES, "UTF-8", false);
    echo <<<EOL
<!DOCTYPE html>
<html>
<head>
<title>$page_title</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<pre>$fileContents</pre>
</body>
</html>
EOL;
} else {
    $parts = pathinfo($filePath);
    $localCss = $parts["filename"].".css";
    $localCssFilePath = $parts["dirname"]."/".$localCss;
    if (file_exists($localCssFilePath)) {
        # Warning, using htmhem.php?url=… will make $localCss not found
        # as the path is relative to htmgem.php and not / !
        $cssContent = "<link type='text/css' rel='StyleSheet' href='$localCss'>";
    } else {
        if (empty($style)) {
            $cssContent =
                 "<style>\n"
                .@file_get_contents("css/htmgem.css")
                ."</style>\n";
        } else {
            if ("none" == $style) {
                $cssContent = "";
            } else {
                if ("/" == $style[0])
                    $href = $style;
                else
                    $href = "/htmgem/css/$style.css";
                $cssContent = "<link type='text/css' rel='StyleSheet' href='$href'>";
            }
        }
    }
    echo <<<EOL
<!DOCTYPE html>
<html lang="fr">
<head>
<title>$page_title</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
$cssContent
</head>
<body>
EOL;

    echo "\n".translateGemToHtml($fileContents);
    echo "</body>\n</html>\n";
}


ob_end_flush();

?>
