<?php

mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

define("NARROW_NO_BREAK_SPACE", "&#8239;");
define("DASHES"
    ,"‒" # U+2012 Figure Dash
    ."–" # U+2013 En Dash
    ."—" # U+2014 Em Dash
    ."⸺" # U+2E3A Two-Em Dash
    ."⸻" # U+2E3B Three-Em Dash (Three times larger than a single char)
);

$style = @$_REQUEST['style'];

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
    global $textDecoration;
    if (!$textDecoration) return;
    markupPreg("__",   "u",   $line);
    markupPreg("\*\*", "strong",   $line);
    markupPreg("//",   "em",   $line);
    markupPreg("~~",   "del", $line);
}

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

    # Warning: using a monospace font editor may not display dashes as they should be!
    # Adds no-break spaces to stick the (EM/EN dashes) to words : aaaaaa – bb – ccccc ==> aaaaaa –$bb$– ccccc
    $text = mb_ereg_replace("([".DASHES."]) ([^".DASHES.".]+) ([".DASHES."])", "\\1".NARROW_NO_BREAK_SPACE."\\2".NARROW_NO_BREAK_SPACE."\\3", $text);

    # Adds no-break space to stick the (EM/EN dashes) to words : aaaaaa – bb. ==> aaaaaa –$bb.
    $text = mb_ereg_replace("([—–]) ([^.]+)\.", "\\1".NARROW_NO_BREAK_SPACE."\\2.", $text);
}

function translateGemToHtml($fileContents) {
    $fileLines = preg_split("/\n/", $fileContents);
    if (empty($fileLines[-1])) array_pop($fileLines); # Don't output a last empty line
    ob_start();
    $mode = null;
    $mode_textAttributes = true;
    foreach ($fileLines as $line) {
        $reDoCount = 0;
        $mode_textAttributes_temp = false;
        while (true) {
            if ($reDoCount>2) {
                error_log("HtmGem: Too many loops, mode == '$mode'");
                $mode = null;
                $reDoCount = 0;
                break;
            }
            $reDoCount += 1;
            $line1 = substr($line, 0, 1); // $line can be modified
            $line2 = substr($line, 0, 2); // in the meantime.
            $line3 = substr($line, 0, 3);
            if (is_null($mode)) {
                if (empty($line)) {
                    echo "<p>&nbsp;</p>\n";
                } elseif ('^^^' == $line3) {
                    $mode_textAttributes = !$mode_textAttributes;
                } elseif ('^' == $line1 and !$mode_textAttributes_temp) {
                    if (preg_match("/^\^\s*(.+)$/", $line, $parts)) {
                        $line = $parts[1];
                        $mode_textAttributes_temp = true;
                    } else {
                        $mode = "raw";
                    }
                    continue;
                } elseif ("#" == $line1) {
                    if (preg_match("/^(#{1,3})\s*(.+)/", $line, $sharps)) {
                        $h_level = strlen($sharps[1]);
                        $text = $sharps[2];
                        htmlPrepare($text);
                        switch ($h_level) {
                            case 1: echo "<h1>".$text."</h1>\n"; break;
                            case 2: echo "<h2>".$text."</h2>\n"; break;
                            case 3: echo "<h3>".$text."</h3>\n"; break;
                        }
                    } else {
                        $mode = "raw";
                        continue;
                    }
                } elseif ("=>" == $line2) {
                    if (preg_match("/^=>\s*([^\s]+)(?:\s+(.*))?$/", $line, $linkParts)) {
                        $url_link = $linkParts[1];
                        $url_label = @$linkParts[2];
                        preg_match("/^([^:]+):/", $url_link, $matches);
                        $url_protocol = @$matches[1];
                        if (empty($url_protocol)) $url_protocol = "local";
                        if (empty(trim($url_label))) {
                            $url_label = $url_link;
                        } else {
                            // the label is humain-made, apply formatting
                            htmlPrepare($url_label);
                            if ($mode_textAttributes xor $mode_textAttributes_temp) addTextAttributes($url_label);
                        }
                        echo "<p><a class='$url_protocol' href='$url_link'>$url_label</a></p>\n";
                    } else {
                        $mode = "raw";
                        continue;
                    }
                } elseif ("```" == $line3) {
                    preg_match("/^```\s*(.*)$/", $line, $matches);
                    $alt_text = trim($matches[1]);
                    if (empty($alt_text)) {
                        echo "<pre>\n";
                    } else {
                        echo "<pre alt='$alt_text' title='$alt_text'>\n";
                    }
                    $mode="pre";
                } elseif (">" == $line1) {
                    echo "<blockquote>\n";
                    $mode = "quote";
                    continue;
                } elseif ("*" == $line1) {
                    echo "<ul>\n";
                    $mode = "ul";
                    continue;
                } else {
                    $mode = "raw";
                    continue;
                }
            } else {
                if ("raw"==$mode) {
                    if (empty($line)) {
                        $line = "&nbsp;";
                    } else {
                        htmlPrepare($line);
                        if ($mode_textAttributes xor $mode_textAttributes_temp)
                            addTextAttributes($line);
                    }
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
                        else {
                            htmlPrepare($quote);
                            if ($mode_textAttributes xor $mode_textAttributes_temp)
                                addTextAttributes($line);
                            echo "<p>".$quote."</p>\n";
                        }
                    } else {
                        echo "</blockquote>\n";
                        $mode = null;
                        continue;
                    }
                } elseif ("ul"==$mode) {
                    if ("*" == $line1) {
                        preg_match("/^\*\s*(.*)$/", $line, $ulParts);
                        $li = $ulParts[1];
                        if (empty($li)) {
                            echo "<li>&nbsp;\n";
                        } else {
                            htmlPrepare($li);
                            if ($mode_textAttributes xor $mode_textAttributes_temp) addTextAttributes($li);
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
        } // while(true)
    }
    $html = ob_get_contents();
    ob_clean();
    return $html;
}

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
