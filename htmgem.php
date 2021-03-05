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
$fileContents = @file_get_contents($filePath);
if (!$fileContents) {
    http_response_code(404);
    die("404: $url");
}

# Removes the Byte Order Mark
$fileContents = preg_replace("/\xEF\xBB\xBF/", "", $fileContents);

$fileLines = preg_split("/\n/", $fileContents);

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

define("NARROW_NO_BREAK_SPACE", "&#8239;");

/**
 * Prepares the raw text to be displayed in HTML environment:
 * * Escapes the HTML entities yet contained in the Gemtext.
 * * Puts thin unbrakable spaces before some characters.
 * @param $text1, $text2 texts to process
 */
function htmlPrepare(&$text) {
    $text = htmlspecialchars($text, ENT_HTML5|ENT_NOQUOTES, "UTF-8", false);
    $text = mb_ereg_replace("\ ([?!:;»€$])", NARROW_NO_BREAK_SPACE."\\1", $text);
    $text = mb_ereg_replace("([«])\ ", "\\1".NARROW_NO_BREAK_SPACE, $text); # Espace fine insécable
}

ob_start();
$mode = null;
$mode_textAttributes = true;
foreach ($fileLines as $line) {
    $reDoCount = 0;
    $mode_textAttributes_temp = false;
    while (true) {
        if ($reDoCount>2) die("Too many loops: ".$mode);
        $reDoCount += 1;
        $line1 = substr($line, 0, 1); // $line can be modified
        $line2 = substr($line, 0, 2); // in the meantime.
        $line3 = substr($line, 0, 3);
        if (is_null($mode)) {
            if (empty($line)) {
                echo "<p>&nbsp;</p>\n";
            } elseif ('^^^' == $line3) {
                $mode_textAttributes = !$mode_textAttributes;
            } elseif ('^' == $line1) {
                if (preg_match("/^\^\s*(.*)$/", $line, $parts)) {
                    $line = $parts[1];
                    $mode_textAttributes_temp = true;
                } else {
                    $mode = "raw";
                }
                continue;
            } elseif ("#" == $line1) {
                preg_match("/^(#{1,3})\s*(.*)/", $line, $sharps);
                $h_level = strlen($sharps[1]);
                $text = $sharps[2];
                htmlPrepare($text);
                switch ($h_level) {
                    case 1: echo "<h1>".$text."</h1>\n"; break;
                    case 2: echo "<h2>".$text."</h2>\n"; break;
                    case 3: echo "<h3>".$text."</h3>\n"; break;
                }
            } elseif ("=>" == $line2) {
                if (preg_match("/^=>\s*([^\s]+)(?:\s+(.*))?$/", $line, $linkParts)) {
                    $url_link = $linkParts[1];
                    $url_label = @$linkParts[2];
                    if (empty(trim($url_label))) {
                        $url_label = $url_link;
                    } else {
                        // the label is humain-made, apply formatting
                        htmlPrepare($url_label);
                    }
                    echo "<p><a href='".$url_link."'>".$url_label."</a></p>\n";
                } else {
                    $mode = "raw";
                    continue;
                }
            } elseif ("```" == $line3) {
                $mode="pre";
                echo "<pre>\n";
            } elseif (">" == $line1) {
                $mode = "quote";
                preg_match("/^>\s*(.*)$/", $line, $quoteParts);
                $quote = $quoteParts[1];
                echo "<blockquote>\n";
                if (empty($quote))
                    echo "<p>&nbsp;</p>\n";
                else
                    htmlPrepare($quote);
                if ($mode_textAttributes xor $mode_textAttributes_temp) addTextAttributes($line);
                    echo "<p>".$quote."</p>\n";
            } elseif ("* " == $line2) {
                echo "<ul>\n";
                $mode = "ul";
                continue;
            } else {
                $mode = "raw";
                continue;
            }
        } else {
            if ("raw"==$mode) {
                htmlPrepare($line);
                if ($mode_textAttributes xor $mode_textAttributes_temp) addTextAttributes($line);
                if (empty($line)) $line = "&nbsp;";
                echo "<p>$line</p>\n";
                $mode = null;
            } elseif ("pre"==$mode) {
                if ("```" == $line3) {
                    echo "</pre>\n";
                    $mode = null;
                } else {
                    htmlPrepare($line);
                    echo $line."\n";
                }
            } elseif ("quote"==$mode) {
                if (">" == $line1) {
                    preg_match("/^>\s*(.*)$/", $line, $quoteParts);
                    $quote = $quoteParts[1];
                    if (empty($quote))
                        echo "<p>&nbsp;</p>\n";
                    else
                        htmlPrepare($quote);
                        echo "<p>".$quote."</p>\n";
                } else {
                    echo "</blockquote>\n";
                    $mode = null;
                    continue;
                }
            } elseif ("ul"==$mode) {
                if ("* " == $line2) {
                    preg_match("/^\*\s*(.*)$/", $line, $ulParts);
                    $li = $ulParts[1];
                    if (empty($li)) {
                        echo "<li>&nbsp;\n";
                    } else {
                        htmlPrepare($li);
                        if ($mode_textAttributes xor $mode_textAttributes_temp) addTextAttributes($line);
                        echo "<li>".$li."\n";
                    }
                } else {
                    echo "</ul>\n";
                    $mode = null;
                    continue;
                }
            } else {
                die("Unexpected mode: $mode!");
            }
        }
        break; // exits the while(true) as no continue occured
    }
}
$body = ob_get_contents();
ob_clean();

# Gets the page title: the first occurrence with # at the line start
mb_ereg("#\s*([^\n]+)\n", $fileContents, $matches);
$page_title = @$matches[1];

# <!-- link type="text/css" rel="StyleSheet" href="/htmgem.css" -->
echo <<<EOL
<!DOCTYPE html>
<html lang="fr">
<head>
<title>$page_title</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
EOL;
include("htmgem.css");
echo <<<EOL
</style>
</head>
<body>
EOL;

echo "\n".$body;
echo "</body>\n</html>\n";
ob_end_flush();

?>
