<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__)."/../lib-htmgem.php";

function parse($text): array {
    return iterator_to_array(\htmgem\gemtextParser($text));
}


final class parserTest extends TestCase {

    public function test_gemtextParser_textLines(): void {
        $this->assertSame(
            array(),
            parse(null),
            "  Null line"
        );
        $this->assertSame(
            array(),
            parse(""),
            "  Empty line"
        );
        $this->assertSame(
            array(
                array("mode"=>"", "text"=>"  foo"),
            ),
            parse("  foo    "),
            "  A text surrounded by spaces"
        );
        $this->assertSame(
            array(
                array(
                    "mode"=>"",
                    "text"=>"  bar"
               ),
            ),
            parse("  bar  \n"),
            "  A text with a line feed after"
        );
        $this->assertSame(
            array(
                array("mode"=>"", "text"=>"foo"),
                array("mode"=>"", "text"=>"bar"),
            ),
            parse("foo\nbar"),
            "  Two lines of text"
        );
    }

    public function test_gemtextParser_link(): void {
        $this->assertSame(
            array(
                array("mode"=>"=>", "link"=>"", "text"=>"")
            ),
            parse("=>"),
            "=> A single equal-greaterthan"
        );
        $this->assertSame(
            array(
                array("mode"=>"=>", "link"=>"https://www.test.com", "text"=>"")
            ),
            parse("=> https://www.test.com"),
            "=> A normal link with no text"
        );
        $this->assertSame(
            array(
                array("mode"=>"=>", "link"=>"https://www.test.com", "text"=>"")
            ),
            parse("=>   https://www.test.com"),
            "=> A link with spaces between the egal-greaterthan and the link"
        );
        $this->assertSame(
            array(
                array("mode"=>"=>", "link"=>"https://www.test.com", "text"=>"text of the link")
            ),
            parse("=> https://www.test.com text of the link      "),
            "=> A link with a text with spaces at the end"
        );
        $this->assertSame(
            array(
                array("mode"=>"", "text"=>" => https://www.test.com text of the link")
            ),
            parse(" => https://www.test.com text of the link"),
            "=> A link with a space before, seen like a text line"
        );
    }

    public function test_gemtextParser_titles(): void {
        $this->assertSame(
            array(
                array("mode"=>"#", "title"=>"")
            ),
            parse("#"),
            "# Single sharp with no data"
        );
        $this->assertSame(
            array(
                array("mode"=>"#", "title"=>"")
            ),
            parse("# "),
            "# A sharp with one space after"
        );
        $this->assertSame(
            array(
                array("mode"=>"#", "title"=>"the title")
            ),
            parse("# the title"),
            "# A normal title"
        );
        $this->assertSame(
            array(
                array("mode"=>"#", "title"=>"the  title")
            ),
            parse("#    the  title"),
            "# A title with spaces right after the sharp"
        );
        $this->assertSame(
            array(
                array("mode"=>"##", "title"=>"Second level title")
            ),
            parse("## Second level title"),
            "## Level two"
        );
        $this->assertSame(
            array(
                array("mode"=>"###", "title"=>"Third level title")
            ),
            parse("### Third level title"),
            "### Level three"
        );
        $this->assertSame(
            array(
                array("mode"=>"###", "title"=>"# Fourth level title")
            ),
            parse("#### Fourth level title"),
            "### Level fourth"
        );
        $this->assertSame(
            array(
                array("mode"=>"###", "title"=>"#")
            ),
            parse("####"),
            "### Level fourth without space"
        );
        $this->assertSame(
            array(
                array("mode"=>"###", "title"=>"##")
            ),
            parse("#####"),
            "### Level five without space"
        );
        $this->assertSame(
            array(
                array("mode"=>"###", "title"=>"##")
            ),
            parse("#####        "),
            "### Level five with spaces and a tabulation at the end"
        );
    }

    public function test_gemtextParser_lists(): void {
        $this->assertSame(
            array(
                array("mode"=>"*", "texts"=>
                    array("")
                )
            ),
            parse("*"),
            "* An item with no data, just the star on a line"
        );
        $this->assertSame(
            array(
                array("mode"=>"*", "texts"=>
                    array("")
                )
            ),
            parse("*   "),
            "* An item with not content but spaces"
        );
        $this->assertSame(
            array(
                array("mode"=>"*", "texts"=>
                    array("hello")
                )
            ),
            parse("* hello"),
            "* A single item"
        );
        $this->assertSame(
            array(
                array("mode"=>"*", "texts"=>
                    array("hello")
                )
            ),
            parse("*hello"),
            "* An item with no space right after the star"
        );
        $this->assertSame(
            array(
                array("mode"=>"*", "texts"=>
                    array("hello")
                )
            ),
            parse("*   hello"),
            "* An item with spaces before"
        );
        $this->assertSame(
            array(
                array("mode"=>"*", "texts"=>
                    array(
                        "hello",
                        "how",
                        "are you?"
                    )
                )
            ),
            parse("* hello\n" . "*  how\n" . "*are you?"),
            "* Several list items"
        );
        $this->assertSame(
            array(
                array("mode"=>"*", "texts"=>
                    array(
                        "hello",
                        "how",
                        "are"
                    )
                ),
                array("mode"=>"", "text"=>"you?")
            ),
            parse("* hello\n" . "*  how\n" . "*are\n" . "you?"),
            "* Several list items, text then an item"
        );
    }

    public function test_gemtextParser_preformated(): void {
        $this->assertSame(
            array(
                array("mode"=>"```", "alt"=>"", "texts"=>
                    array(
                    )
                )
            ),
            parse("```"),
            "``` A preformated text with just the block, nothing after"
        );
        $this->assertSame(
            array(
                array("mode"=>"```", "alt"=>"alternative text", "texts"=>
                    array(
                    )
                )
            ),
            parse("```alternative text"),
            "``` A preformated text with just the alternative text"
        );
        $this->assertSame(
            array(
                array("mode"=>"```", "alt"=>"alternative text", "texts"=>
                    array(
                    )
                )
            ),
            parse("```   alternative text  "),
            "``` A preformated text with just the alternative text surrounded by spaces"
        );
        $this->assertSame(
            array(
                array("mode"=>"```", "alt"=>"", "texts"=>
                    array("  first line")
                )
            ),
            parse("```   \n" . "  first line   "),
            "``` A preformated text with one line"
        );
        $this->assertSame(
            array(
                array("mode"=>"```", "alt"=>"", "texts"=>
                    array(
                        "  first line"
                    )
                )
            ),
            parse("```   \n" . "  first line  \n"),
            "``` A preformated text with one line and a line feed"
        );
        $this->assertSame(
            array(
                array("mode"=>"```", "alt"=>"", "texts"=>
                    array(
                        "  first line",
                        "second line"
                    )
                )
            ),
            parse("```   \n" . "  first line  \n" . "second line"),
            "``` A preformated text with two lines separated by one line feed"
        );
        $this->assertSame(
            array(
                array("mode"=>"```", "alt"=>"", "texts"=>
                    array(
                    )
                )
            ),
            parse("```   \n" . "``` text to ignore"),
            "``` A preformated text made of two backticks lines one after the other"
        );
        $this->assertSame(
            array(
                array("mode"=>"```", "alt"=>"", "texts"=>
                    array(
                        ""
                    )
                )
            ),
            parse("```   \n" . "   \n" ."``` text to ignore"),
            "``` A preformated text made of two backticks lines separated by a blank line"
        );
        $this->assertSame(
            array(
                array("mode"=>"```", "alt"=>"", "texts"=>
                    array(
                    )
                )
            ),
            parse("```   \n" . "``` text to ignore\n"),
            "``` A preformated text made of two backticks lines followed by a line feed"
        );
        $this->assertSame(
            array(
                array("mode"=>"```", "alt"=>"", "texts"=>
                    array(
                        " one line  of    preformated text"
                    )
                ),
            ),
            parse("```   \n" . " one line  of    preformated text  \n" . "``` text to ignore"),
            "``` A preformated text with one line"
        );
        $this->assertSame(
            array(
                array("mode"=>"```", "alt"=>"", "texts"=>
                    array(
                        " two lines  of",
                        "         preformated text"
                    )
                ),
            ),
            parse("```\n" . " two lines  of  \n" . "         preformated text\n" . "``` text to ignore"),
            "``` A preformated text with two lines"
        );
    }

    public function test_gemtextParser_quoted(): void {
        $this->assertSame(
            array(
                array("mode"=>">", "texts"=>
                    array(
                        ""
                    )
                )
            ),
            parse(">"),
            "> Quotation text with just greaterthan, nothing after"
        );
        $this->assertSame(
            array(
                array("mode"=>">", "texts"=>
                    array(
                        "the quoted text"
                    )
                )
            ),
            parse(">   the quoted text   "),
            "> Normal quotation text"
        );
        $this->assertSame(
            array(
                array("mode"=>">", "texts"=>
                    array(
                        "the quoted text"
                    )
                )
            ),
            parse(">the quoted text   "),
            "> Normal quotation text, no space after the greaterthan"
        );
        $this->assertSame(
            array(
                array("mode"=>">", "texts"=>
                    array(
                        "",
                        "",
                        ""
                    )
                )
            ),
            parse(">   \n> \n>   "),
            "> Quotation text three times spaces"
        );
        $this->assertSame(
            array(
                array("mode"=>">", "texts"=>
                    array(
                        "A",
                        "B",
                        "C"
                    )
                )
            ),
            parse("> A  \n>B \n>    C "),
            "> Quotation text three times letters with spaces"
        );
        $this->assertSame(
            array(
                array("mode"=>">", "texts"=>
                    array(
                        "A",
                        "",
                        "C"
                    )
                )
            ),
            parse(">A  \n>\n>    C "),
            "> One group of three withn an empty one in the middle"
        );
    }

    public function test_gemtextParser_textdecoration(): void {
        $this->assertSame(
            array(
                array("mode"=>"^^^")
            ),
            parse("^^^"),
            "^^^ Normal text decoration toggle"
        );
        $this->assertSame(
            array(
                array("mode"=>"", "text"=>" ^^^ Text decoration toggle with a space before")
            ),
            parse(" ^^^ Text decoration toggle with a space before"),
            "^^^ Text decoration toggle with a space before"
        );
        $this->assertSame(
            array(
                array("mode"=>"^^^"),
                array("mode"=>"^^^")
            ),
            parse("^^^ Text decoration toggle without a space before\n" . "^^^"),
            "^^^ Text decoration toggle without a space before"
        );
    }
}
