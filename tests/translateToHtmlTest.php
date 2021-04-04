<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

$dirname_file = dirname(__FILE__);
require_once "$dirname_file/../lib-htmgem.inc.php";
require_once "$dirname_file/utils.inc.php";
require_once "$dirname_file/../lib-io.inc.php";

function translateHtml($text): string {
    $gt_html = new htmgem\GemtextTranslate_html($text);
    return strval($gt_html->translatedGemtext);
}

final class translateToHtmlTest extends TestCase {

    protected static function noSeveralSpaces($text): string {
        # Replaces several spaces (0x20) by only one
        return preg_replace("/  +/", " ", $text);
    }

    public function test_translate_gemtext_smallTextSets(): void {
        $line1 = "  Hello, how are you? ";
        $line2 = " Nice to meet you!    ";
        $rline1 = self::noSeveralSpaces(rtrim($line1));
        $rline2 = self::noSeveralSpaces(rtrim($line2));
        $this->assertSame(
            "",
            translateHtml(null),
            "Null line"
        );
        $this->assertSame(
            "",
            translateHtml(""),
            "Empty line"
        );
        $this->assertSame(
            "<p>$rline1</p>\n",
            translateHtml($line1),
            "Only one line"
        );
        $this->assertSame(
            "<p>$rline1</p>\n",
            translateHtml($line1."\n"),
            "Only one line with a line feed"
        );
        $this->assertSame(
            "<p>$rline1</p>\n<p>$rline2</p>\n",
            translateHtml($line1."\n".$line2),
            "Two lines, one line feed in between"
        );
        $this->assertSame(
            "<p>$rline1</p>\n<p>$rline2</p>\n",
            translateHtml("$line1\n$line2\n"),
            "Two lines, one line feed after each"
        );
    }

    public function test_decoration(): void {
        $this->assertSame(
            "<p>:<strong></strong></p>\n",
            translateHtml(":**"),
            "** Empty strong: the strongness goes until the end"
        );
        $this->assertSame(
            "<p>:<strong>**</strong></p>\n",
            translateHtml(":****"),
            "** Two stars"
        );
        $this->assertSame(
            "<p>:<strong>ok</strong></p>\n",
            translateHtml(":**ok**"),
            "** normal case with a word"
        );
        $this->assertSame(
            "<p>:<strong>nice</strong></p>\n",
            translateHtml(":**nice"),
            "** a word with no end stars"
        );
        $this->assertSame(
            "<p>:<strong>nice</strong></p>\n",
            translateHtml(":**nice"),
            "** a word with no end stars"
        );
        $this->assertSame(
            "<p>:<strong>**one two three</strong></p>\n",
            translateHtml(":****one two three"),
            "** a word with no end stars"
        );
    }


    #TODO: don't stop when problems are found, list all the faulty files
    public function test_translate_html_files_with_html(): void {
        /** NOTE: the UTF-16 files must result in the same content as UTF-8 ones.
         * command to convert from UTF-8 to UTF-16: iconv -f utf8 -r utf16 text.gmi
         */
        foreach(getGmiFiles(dirname(__FILE__)."/files_with_html") as $filePathname) {
            $fileContentGmi = file_get_contents($filePathname);
            \htmgem\io\convertToUTF8($fileContentGmi);
            $fileContentHtml = file_get_contents($filePathname.".html");
            $this->assertSame(
                $fileContentHtml,
                translateHtml($fileContentGmi),
                "Translation to HTML: $filePathname"
            );
        }
    }

}
