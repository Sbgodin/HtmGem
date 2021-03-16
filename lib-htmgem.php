<?php

namespace htmgem;

mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

$style = @$_REQUEST['style'];

define("NARROW_NO_BREAK_SPACE", "&#8239;");
define("DASHES"
    ,"‒" # U+2012 Figure Dash
    ."–" # U+2013 En Dash
    ."—" # U+2014 Em Dash
    ."⸺" # U+2E3A Two-Em Dash
    ."⸻" # U+2E3B Three-Em Dash (Three times larger than a single char)
);

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

?>
