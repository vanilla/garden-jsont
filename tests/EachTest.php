<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\JSON\Tests;

use Garden\JSON\InvalidSpecException;
use Garden\JSON\Transformer;
use PHPUnit\Framework\TestCase;

class EachTest extends TestCase {
    /**
     * Arrays can be iterated over with the `$each` expression.
     */
    public function testBasicEach() {
        $t = new Transformer([
            '$each' => '/',
            '$item' => [
                'id' => 'ID',
            ],
        ]);

        $actual = $t->transform([
            'a' => ['ID' => 1],
            'b' => ['ID' => 2]
        ]);


        $this->assertSame(['a' => ['id' => 1], 'b' => ['id' => 2]], $actual);
    }

    /**
     * Strip string keys.
     */
    public function testIndexEach() {
        $t = new Transformer([
            '$each' => '/',
            '$item' => '',
            '$key' => '$index',
        ]);

        $actual = $t(['a' => 'a', 'b' => 'b']);
        $this->assertSame(['a', 'b'], $actual);
    }

    /**
     * The key can also be a spec.
     */
    public function testKeySpec() {
        $t = new Transformer([
            '$each' => '/',
            '$item' => 'id',
            '$key' => 'name'
        ]);

        $actual = $t([['id' => 1, 'name' => 'foo']]);
        $this->assertSame(['foo' => 1], $actual);
    }

    /**
     * An `$item` without `$each` is an exception.
     */
    public function testMissingEach() {
        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage("Missing key \$each at /");
        $t = new Transformer(['$item' => 'b']);
        $t([]);
    }

    /**
     * An `$item` without `$each` is an exception.
     */
    public function testMissingItem() {
        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage("Missing key \$item at /");
        $t = new Transformer(['$each' => 'b']);
        $t([]);
    }

    /**
     * An each reference that doesn't resolve should return null.
     */
    public function testEachNotFound() {
        $t = new Transformer(['$each' => 'a', '$item' => 'b']);
        $actual = $t(['fff']);
        $this->assertSame(null, $actual);
    }
}
