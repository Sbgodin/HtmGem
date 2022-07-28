<?php declare(strict_types=1);

namespace htmgem;

mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

/**
 * Resolve $path interpretating / . and ..
 * @param $path str
 * @returns "/" if .. goes above the limit
 */
function resolve_path($path) {
    if (empty($path)) return "";
    $absolute = "/"==$path[0];
    $parts = array_filter(explode("/", $path), 'strlen');
    $chuncks = array();
    foreach ($parts as $part) {
        if ('.' == $part) continue;
        if ('..' == $part) {
            if (is_null(array_pop($chuncks))) return "/";
        } else {
            $chuncks[] = $part;
        }
    }
    $output = implode("/", $chuncks);
    if ($absolute) $output = "/".$output;
    return $output;
}

/**
 * Splits link (without .. or .) into parts along with direct url access.
 * @param url
 *
 * Ex. /dir1/dir2/page.gmi
 * --> "dir1" --> "/dir1"
 * --> "dir2" --> "/dir1/dir2"
 * --> "page.gmi" --> "/dir2/page.gmi"
 */
function split_path_links($path, $prefix="") {
    $parts = array_filter(explode("/", $path), 'strlen');
    if (empty($parts)) return array();
    if ("/"==$path[0])
        $stack = "/";
    else
        $stack = "";
    $output = array();
    $slash = "";
    foreach ($parts as $part) {
        $stack .= $slash.$part;
        $output[$part] = $prefix.$stack;
        $slash = "/";
    }
    return $output;
}

/**
 * Parses the gemtext and generates the internal format version
 * @param str $fileContents the gemtext to parse
 */
function gemtextParser($fileContents) {
    if (empty($fileContents)) return array();
    $fileContents = rtrim($fileContents); // removes last empty line
    $fileLines = mb_split("\n|\r\n?", $fileContents); // Unix, Mac, Windows line feeds
    $mode = null;
    $current = array();
    foreach ($fileLines as $line) {
        $reDoCount = 0;
        $mode_textAttributes_temp = false;
        while (true) {
            /* The continue instruction is used to make another turn when there is a transition
             * between two modes. */
            if ($reDoCount>1) {
                die("HtmGem: Too many loops, mode == '$mode'");
            }
            $reDoCount += 1;
            $line1 = substr($line, 0, 1); // $line can be modified
            $line2 = substr($line, 0, 2); // in the meantime.
            $line3 = substr($line, 0, 3);
            if (is_null($mode)) {
                if ('^^^' == $line3) {
                    yield array("mode" => "^^^");
                } elseif ("#" == $line1) {
                    preg_match("/^(#{1,3})\s*(.+)?/", $line, $matches);
                    yield array("mode" => $matches[1], "title" => trim($matches[2]??""));
                } elseif ("=>" == $line2) {
                    preg_match("/^=>\s*([^\s]+)(?:\s+(.*))?$/", $line, $matches);
                    yield array("mode" => "=>", "link" => trim($matches[1]??""), "text" => trim($matches[2]??""));
                } elseif ("```" == $line3) {
                    preg_match("/^```\s*(.*)$/", $line, $matches);
                    $current = array("mode" => "```", "alt" => trim($matches[1]), "texts" => array());
                    $mode="```";
                } elseif (">" == $line1) {
                    preg_match("/^>\s*(.*)$/", $line, $matches);
                    $current = array("mode" => ">", "texts" => array(trim($matches[1])));
                    $mode = ">";
                } elseif ("*" == $line1) {
                    preg_match("/^\*\s*(.*)$/", $line, $matches);
                    $current = array("mode" => "*", "texts" => array(trim($matches[1])));
                    $mode = "*";
                } else {
                    // text_line
                    yield array("mode"=>"", "text" => rtrim($line));
                }
            } else {
                if ("```"==$mode) {
                    if ("```" == $line3) {
                        yield $current;
                        $current = array();
                        $mode = null;
                    } else {
                        $current["texts"] []= rtrim($line); // No ltrim() as it’s a preformated text!
                    }
                } elseif (">"==$mode) {
                    if (">" == $line1) {
                        preg_match("/^>\s*(.*)$/", $line, $matches);
                        $current["texts"] []= trim($matches[1]);
                    } else {
                        yield $current;
                        $current = array();
                        $mode = null;
                        continue;
                    }
                } elseif ("*"==$mode) {
                    if ("*" == $line1) {
                        preg_match("/^\*\s*(.*)$/", $line, $matches);
                        $current["texts"] []= trim($matches[1]);
                    } else {
                        yield $current;
                        $current = array();
                        $mode = null;
                        continue;
                    }
                } else {
                    die("Unexpected mode: $mode!");
                }
            }
            break; // exits the while(true) as no continue occured
        } // while(true)
    }// foreach
    if ($current) yield $current; # File ends before the block.
} // gemtextParser


/**
 * Translates the internal format into a gemtext.
 * Uses cases:
 *
 * - test suites
 * - serialisation easier with a text content
 * - normalization (trimming spaces for instance)
 */
class GemtextTranslate_gemtext {

    function __construct($parsedGemtext) {
        if (empty($parsedGemtext)) $parsedGemtext = "";
        // to delete the last empty lines
        $parsedGemtext = rtrim($parsedGemtext);
        // The text must be parsed
        $this->parsedGemtext = gemtextParser($parsedGemtext);
        $this->translate();
    }

    protected function translate() {
        $output = "";
        foreach ($this->parsedGemtext as $node) {
            $mode = $node["mode"];
            switch($mode) {
                case "":
                    $output .= $node["text"]."\n";
                    break;
                case "*":
                    foreach ($node["texts"] as $text) {
                        $output .= "* $text\n";
                    }
                    break;
                case "```":
                    $alt = $node["alt"];
                    if (empty($alt))
                        $output .= "```\n";
                    else
                        $output .= "``` $alt\n";
                    foreach ($node["texts"] as $text) {
                        $output .= "$text\n";
                    }
                    $output .= "```\n";
                    break;
                case ">":
                    foreach ($node["texts"] as $text) {
                        if (empty($text))
                            $output .= ">\n";
                        else
                            $output .= "> $text\n";
                    }
                    break;
                case "=>":
                    $linkText = $node["text"];
                    $link = $node["link"];
                    if (!empty($linkText)) $linkText = " $linkText";
                    if (!empty($link)) $link = " $link";
                    $output .= "=>".$link.$linkText."\n";
                    break;
                case "#":
                case "##":
                case "###":
                    $output .= "$mode ".$node["title"]."\n";
                    break;
                case "^^^":
                    $output .= "^^^\n";
                    break;
                default:
                    die("Unknown mode: '{$node["mode"]}'\n");
            }
        }

        $this->translatedGemtext = $output;
    }

    public function __toString() {
        return $this->translatedGemtext;
    }
} // GemtextTranslate_gemtext


/**
 * Translates the internal format to HTML
 */
class GemtextTranslate_html {

    protected $cssList = array();
    protected $pageTitle = "";
    public $translatedGemtext;

    /**
     * @param $parsedGemtext the gemtext internal format
     * @param $textDecoration bool to interpret or not the text decoration
     * @param $urlPrefix the prefix to prepend if the URL rewriting is not on
     * @param $currentPageDir the current directory, to be used without URL rewriting
     */
    function __construct($parsedGemtext, $textDecoration=true, $urlPrefix=null, $currentPageDir=null) {
        $this->urlPrefix = $urlPrefix;
        $this->currentPageDir = $currentPageDir;
        if (empty($parsedGemtext)) $parsedGemtext = "";
        // to delete the last empty lines
        $parsedGemtext = rtrim($parsedGemtext);
        // The text must be parsed
        $parsedGemtext = gemtextParser($parsedGemtext);
        $this->parsedGemtext = $parsedGemtext;
        $this->translate($textDecoration);
    }

    function addCss($css) {
        $this->cssList []= $css;
    }

    function getCss() { return $this->cssList; }
    function getTitle() { return $this->pageTitle; }

    const NARROW_NO_BREAK_SPACE = "&#8239;";
    const DASHES
        ="‒" # U+2012 Figure Dash
        ."–" # U+2013 En Dash
        ."—" # U+2014 Em Dash
        ."⸺" # U+2E3A Two-Em Dash
        ."⸻" # U+2E3B Three-Em Dash (Three times larger than a single char)
    ;

    /**
     * Replaces markups things like __underlined__ to <u>underlined</u>.
     * @param $instruction the characters to replace, ex. _
     * @param $markup the markup to replace to, ex. "u" to get <u>…</u>
     * @param &$text where to replace.
     */
    protected static function markupPreg($instruction, $markup, &$text) {
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
    protected static function addTextDecoration(&$line) {
        self::markupPreg("__",   "u",      $line);
        self::markupPreg("\*\*", "strong", $line);
        self::markupPreg("//",   "em",     $line);
        self::markupPreg("~~",   "del",    $line);
    }

    /**
     * Prepares the raw text to be displayed in HTML environment:
     * * Escapes the HTML entities yet contained in the Gemtext.
     * * Puts thin unbrakable spaces before some characters.
     * @param $text1, $text2 texts to process
     */
    protected static function htmlPrepare(&$text) {
        if (empty($text)) {
            $text = "&nbsp;";
        } else {
            $text = htmlspecialchars($text, ENT_HTML5|ENT_QUOTES, "UTF-8", true);
            $text = mb_ereg_replace("\ ([?!:;»€$])", self::NARROW_NO_BREAK_SPACE."\\1", $text);
            $text = mb_ereg_replace("([«])\ ", "\\1".self::NARROW_NO_BREAK_SPACE, $text); # Espace fine insécable

            # Warning: using a monospace font editor may not display dashes as they should be!
            # Adds no-break spaces to stick the (EM/EN dashes) to words : aaaaaa – bb – ccccc ==> aaaaaa –$bb$– ccccc
            $text = mb_ereg_replace("([".self::DASHES."]) ([^".self::DASHES.".]+) ([".self::DASHES."])", "\\1".self::NARROW_NO_BREAK_SPACE."\\2".self::NARROW_NO_BREAK_SPACE."\\3", $text);

            # Adds no-break space to stick the (EM/EN dashes) to words : aaaaaa – bb. ==> aaaaaa –$bb.
            $text = mb_ereg_replace("([—–]) ([^.]+)\.", "\\1".self::NARROW_NO_BREAK_SPACE."\\2.", $text);
        }
    }

    protected static function spacesCompress(&$text) {
        # Replaces several spaces (0x20) by only one
        if (empty($text)) $text = "";
        $text = preg_replace("/  +/", " ", $text);
    }

    public function translate($textDecoration=true) {
        $output = "";
        foreach ($this->parsedGemtext as $node) {
            $mode = $node["mode"];
            switch($mode) {
                case "":
                    $text = $node["text"];
                    self::spacesCompress($text);
                    self::htmlPrepare($text);
                    if ($textDecoration) self::addTextDecoration($text);
                    $output .= "<p>$text</p>\n";
                    break;
                case "*":
                    $output .= "<ul>\n";
                    foreach ($node["texts"] as $text) {
                        self::spacesCompress($text);
                        self::htmlPrepare($text);
                        if ($textDecoration) self::addTextDecoration($text);
                        $output .= "<li>$text\n";
                    }
                    $output .= "</ul>\n";
                    break;
                case "```":
                    $text = implode("\n", $node["texts"]);
                    self::htmlPrepare($text);
                    $alt = $node["alt"];
                    $output .= "<pre alt='$alt'>\n$text\n</pre>\n";
                    break;
                case ">":
                    $output .= "<blockquote>\n";
                    foreach ($node["texts"] as $text) {
                        self::spacesCompress($text);
                        self::htmlPrepare($text);
                        if ($textDecoration) self::addTextDecoration($text);
                        $output .= "<p>$text</p>\n";
                    }
                    $output .= "</blockquote>\n";
                    break;
                case "=>":
                    $link = $node["link"];
                    $linkText = $node["text"];
                    if (empty($linkText)) {
                        $linkText = $link;
                        self::htmlPrepare($linkText);
                    } else {
                        self::spacesCompress($linkText);
                        // Don't double encode, just escapes quotes, "<" and ">".
                        // So "I'm&gt" becomes "I&apos;&gt". The & remains untouched.
                        $link = htmlspecialchars($link, ENT_HTML5|ENT_QUOTES, "UTF-8", false);
                        self::htmlPrepare($linkText);
                        if ($textDecoration) self::addTextDecoration($linkText);
                    }
                    preg_match("/^([^:]+):/", $link, $matches);
                    $protocol = @$matches[1]??"local";
                    if ("local"==$protocol) {
                        if (!is_null($this->urlPrefix)) { // No URL rewriting
                            $link = $this->currentPageDir."/".$link;
                            $link = resolve_path($link);
                            $link = $this->urlPrefix.$link;
                        }
                        $newWindow = "";
                    } else {
                        $newWindow = "target='_blank' ";
                    }
                    $output .= "<p><a {$newWindow}class='$protocol' href='$link'>$linkText</a></p>\n";
                    break;
                case "#":
                    $title = $node["title"];
                    self::spacesCompress($linkText);
                    self::htmlPrepare($title);
                    if (empty($this->pageTitle)) $this->pageTitle = $title;
                    $output .= "<h1>$title</h1>\n";
                    break;
                case "##":
                    $title = $node["title"];
                    self::spacesCompress($linkText);
                    self::htmlPrepare($title);
                    $output .= "<h2>$title</h2>\n";
                    break;
                case "###":
                    $title = $node["title"];
                    self::spacesCompress($linkText);
                    self::htmlPrepare($title);
                    $output .= "<h3>$title</h3>\n";
                    break;
                case "^^^":
                    $textDecoration = !$textDecoration;
                    break;
                default:
                    die("Unknown mode: '{$node["mode"]}'\n");
            }
        }

        $this->translatedGemtext = $output;
    }

} // GemTextTranslate_html

?>
