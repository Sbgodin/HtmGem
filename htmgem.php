<?php

mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

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
    die("404: $url");
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

/**
 * Replaces markups things like __underlined__ to <u>underlined</u>.
 * @param $instruction the characters to replace, ex. _
 * @param $markup the markup to replace to, ex. "u" to get <u>…</u>
 * @param &$text where to replace.
 */
function markupPreg($instruction, $markup, &$text) {
    $output = $text;

    # Replaces couples "__word__" into "<i>word</i>".
    $output = mb_ereg_replace("${instruction}(.+?)${instruction}", "<{$markup}>\\1</{$markup}>", $output);

    # Replaces a remaining __ into "<i>…</i>" to the end of the line.
    $output = mb_ereg_replace("${instruction}(.+)?", "<{$markup}>\\1</{$markup}>", $output);

    $text = $output;
}


/**
 * Adds text attributes sucj as underline, bold, … to $line
 * @param $line the line to process
 */
function addTextAttributes(&$line) {
    markupPreg("__",   "u",   $line);
    markupPreg("\*\*", "strong",   $line);
    markupPreg("//",   "em",   $line);
    markupPreg("~~",   "del", $line);
}

/**
 * Escapes the HTML entities yet contained in the Gemtext, keeps multiple spaces.
 * @param $text1, $text2 texts to process
 */
function htmlEscape(&$text) {
    $text = htmlspecialchars($text, ENT_HTML5|ENT_NOQUOTES, "UTF-8", false);
    $text = mb_ereg_replace("\ ([?!:;»€$])", "&#8239;\\1", $text); # Espace fine insécable
    $text = mb_ereg_replace("([«])\ ", "\\1&#8239;", $text); # Espace fine insécable
}

$mode = null;
$mode_textAttributes = true;
foreach ($fileLines as $line) {
    $reDoCount = 0;
    while (true) {
        if ($reDoCount>1) die("Too many loops");
        $reDoCount += 1;
        $line1 = substr($line, 0, 1); // $line can be modified
        $line2 = substr($line, 0, 2); // in the meantime.
        $line3 = substr($line, 0, 3);
        if (is_null($mode)) {
            if (empty($line)) {
                print("<p>&nbsp;</p>\n");
            } elseif (b"\xEF\xBB\xBF" == $line3) {
                # Removes the Byte Order Mark
                $line = substr($line, 3);
                continue;
            } elseif ("#" == $line1) {
                preg_match("/^(#{1,3})\s*(.*)/", $line, $sharps);
                $h_level = strlen($sharps[1]);
                $text = $sharps[2];
                htmlEscape($text);
                switch ($h_level) {
                    case 1: print("<h1>".$text."</h1>\n"); break;
                    case 2: print("<h2>".$text."</h2>\n"); break;
                    case 3: print("<h3>".$text."</h3>\n"); break;
                }
            } elseif ("=>" == $line2) {
                preg_match("/^=>\s*([^\s]+)(\s+(.*))?$/", $line, $linkParts);
                $url_link = $linkParts[1];
                $url_label = $linkParts[2];
                if (empty(trim($url_label))) {
                    $url_label = $url_link;
                } else {
                    // the label is humain-made, apply formatting
                    htmlEscape($url_label);
                }
                print("<p><a href='".$url_link."'>".$url_label."</a></p>\n");
            } elseif ('"""' == $line3) {
                $mode_textAttributes = !$mode_textAttributes;
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
                    htmlEscape($quote);
                    if ($mode_textAttributes) addTextAttributes($line);
                    print("<p>".$quote."</p>\n");
            } elseif ("*" == $line1 && "**" != $line2) {
                $mode = "ul";
                print("<ul>\n");
                continue;
            } else {
                htmlEscape($line);
                if ($mode_textAttributes) addTextAttributes($line);
                print("<p>$line</p>\n");
            }
        } elseif ("pre"==$mode) {
            if ("```" == $line3) {
                $mode=null;
                print("</pre>\n");
            } else {
                htmlEscape($line);
                print($line."\n");
            }
        } elseif ("quote"==$mode) {
            if (">" == $line1) {
                preg_match("/^>\s*(.*)$/", $line, $quoteParts);
                $quote = $quoteParts[1];
                if (empty($quote))
                    print("<p>&nbsp;</p>\n");
                else
                    htmlEscape($quote);
                    print("<p>".$quote."</p>\n");
            } else {
                $mode=null;
                print("</blockquote>\n");
                continue;
            }
        } elseif ("ul"==$mode) {
            if ("*" == $line1 && "**" != $line2) {
                preg_match("/^\*\s*(.*)$/", $line, $ulParts);
                $li = $ulParts[1];
                if (empty($li))
                    print("<li>&nbsp;\n");
                else
                    htmlEscape($li);
                    addTextAttributes($li);
                    print("<li>".$li."\n");
            } else {
                $mode = null;
                print("</ul>\n");
                continue;
            }
        }
        break; // Do one loop, except if required
    }
}

echo "</body>\n</html>\n";
ob_end_flush();
?>
