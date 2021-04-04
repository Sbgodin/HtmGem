<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

$dirname_file = dirname(__FILE__);
require_once "$dirname_file/../lib-htmgem.inc.php";
require_once "$dirname_file/utils.inc.php";
require_once "$dirname_file/../lib-io.inc.php";

function translate($text): string {
    return strval(new htmgem\GemtextTranslate_gemtext($text));
}

final class translateToGemtextTest extends TestCase {

    public function test_translate_gemtext_smallTextSets(): void {
        $line1 = "  Hello, how are you? ";
        $line2 = " Nice to meet you!    ";
        $rline1 = rtrim($line1);
        $rline2 = rtrim($line2);
        $this->assertSame(
            "",
            translate(null),
            "Null line"
        );
        $this->assertSame(
            "",
            translate(""),
            "Empty line"
        );
        $this->assertSame(
            "$rline1\n",
            translate($line1),
            "Only one line"
        );
        $this->assertSame(
            "$rline1\n",
            translate($line1),
            "Only one line with a line feed"
        );
        $this->assertSame(
            "$rline1\n$rline2\n",
            translate("$line1\n$line2"),
            "Two lines, one line feed in between"
        );
        $this->assertSame(
            "$rline1\n$rline2\n",
            translate("$line1\n$line2"),
            "Two lines, one line feed after each"
        );
    }

    #TODO: don't stop when problems are found, list all the faulty files
    public function test_translate_gemtext_files(): void {
        foreach(getFiles(dirname(__FILE__)."/..", "gmi") as $filePathname) {
            $fileContent = file_get_contents($filePathname);
            \htmgem\io\convertToUTF8($fileContent);
            $this->assertSame(
                $fileContent,
                translate($fileContent),
                "The same file without extra space translated into itself: $filePathname"
            );
        }
    }

}
