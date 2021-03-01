<?php

if (isset($_REQUEST["url"]))
    $url = $_REQUEST["url"];
elseif (isset($_SERVER["QUERY_STRING"]))
    $url = "/".$_SERVER["QUERY_STRING"];
else
    $url = "/index.gmi";

$GMI_DIR = $_SERVER['DOCUMENT_ROOT'];

$filePath = $GMI_DIR.$url;
$fileContent = @file_get_contents($filePath);
if (!$fileContent) {
    http_response_code(404);
    die("404: $filePath $GMI_DIR $url");
}

$fileLines = preg_split("/\n/", $fileContent);

ob_start();


echo(<<<EOL
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>HTM Gem</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!-- link type="text/css" rel="StyleSheet" href="/htmgem.css" -->
    <style>

EOL
);
include("htmgem.css");
echo(<<<EOL
</style>
</head>
<body>
EOL);

$mode = null;
foreach ($fileLines as $line) {
    $reDo = true;
    $line1 = substr($line, 0, 1);
    $line2 = substr($line, 0, 2);
    $line3 = substr($line, 0, 3);
    while ($reDo) {
        $reDo = false; # Change in modes need to redo one loop as they can’t handle the case
        if (is_null($mode)) {
            if (empty($line)) {
                print("<p>&nbsp;</p>\n");
            } elseif ("#" == $line1) {
                preg_match("/^(#{1,3})\s*(.*)/", $line, $sharps);
                $h_level = strlen($sharps[1]);
                $text = $sharps[2];
                switch ($h_level) {
                    case 1: print("<h1>".$text."</h1>\n"); break;
                    case 2: print("<h2>".$text."</h2>\n"); break;
                    case 3: print("<h3>".$text."</h3>\n"); break;
                }
            } elseif ("=>" == $line2) {
                preg_match("/^=>\s*([^\s]+)\s*(.*)$/", $line, $linkParts);
                $url_link = $linkParts[1];
                $url_label = $linkParts[2];
                if (empty($url_label)) $url_label = $url_link;
                print("<p><a href='".$url_link."'>".$url_label."</a></p>\n");
            } elseif ("```" == $line3) {
                $mode="pre";
                print("<pre>\n");
            } elseif (">" == $line1) {
                $mode = "quote";
                preg_match("/^>\s*(.*)$/", $line, $quoteParts);
                $quote = $quoteParts[1];
                print("<blockquote>\n");
                if (empty($quote))
                    print("<p>&nbsp;</p>\n");
                else
                    print("<p>".$quoteParts[1]."</p>\n");
            } elseif ("*" == $line1) {
                $mode = "ul";
                $reDo = true;
                print("<ul>\n");
            } else {
                print("<p>".$line."</p>\n");
            }
        } elseif ("pre"==$mode) {
            if ("```" == $line3) {
                $mode=null;
                print("</pre>\n");
            } else {
                print($line."\n");
            }
        } elseif ("quote"==$mode) {
            if (">" == $line1) {
                preg_match("/^>\s*(.*)$/", $line, $quoteParts);
                $quote = $quoteParts[1];
                if (empty($quote))
                    print("<p>&nbsp;</p>\n");
                else
                    print("<p>".$quote."</p>\n");
            } else {
                print("</blockquote>\n");
                $mode=null;
                $reDo=true;
            }
        } elseif ("ul"==$mode) {
            if ("*" == $line1) {
                preg_match("/^\*\s*(.*)$/", $line, $ulParts);
                $li = $ulParts[1];
                if (empty($li))
                    print("<li>&nbsp;\n");
                else
                    print("<li>".$ulParts[1]."\n");
            } else {
                $mode = null;
                print("</ul>\n");
            }
        }
    }
}

ob_end_flush();

?>
