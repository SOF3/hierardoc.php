<?php

declare(strict_types=1);

namespace SOFe\Hierardoc;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TagsTest extends TestCase {
    public function testOneLine() {
        $tags = Tags::parse('/** @phpstan-var string $string */');
        self::assertSame($tags->getGroup("phpstan")->getValue("var"), 'string $string');
    }

    public function testMulti() {
        $tags = Tags::parse('/**
            * @a b
            @c   d
              @e-f g
            @h-i
    * 

        
            @j-k-l
            @j-m n o
        */');

        self::assertSame($tags->getValue("a"), "b");
        self::assertSame($tags->getValue("c"), "d");
        self::assertSame($tags->getGroup("e")->getValue("f"), "g");
        self::assertTrue($tags->getGroup("h")->hasValue("i"));
        self::assertSame($tags->getGroup("h")->getValue("i"), "");
        self::assertSame($tags->getGroup("j")->getGroup("k")->getValue("l"), "");
        self::assertSame($tags->getGroup("j")->getValue("m"), "n o");
    }

    public function testHyphenErrorForGroup() {
        $tags = Tags::parse('/** @phpstan-var string $string */');
        $this->expectException(InvalidArgumentException::class);
        $tags->getGroup("phpstan-var");
    }

    public function testHyphenErrorForValue() {
        $tags = Tags::parse('/** @phpstan-var string $string */');
        $this->expectException(InvalidArgumentException::class);
        $tags->getValue("phpstan-var");
    }
}
