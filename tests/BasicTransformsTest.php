<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\JSON\Tests;

use Garden\JSON\Transformer;
use PHPUnit\Framework\TestCase;


class BasicTransformsTest extends TestCase {


    /**
     * Test some basic transforms.
     *
     * @param array $spec The spec to test.
     * @param array $expected The expected result.
     *
     * @dataProvider provideTransformTests
     */
    public function testTransform(array $spec, array $expected) {
        $json = [
            'bar' => 'foo',
            'foo' => 'baz',
            'nested' => [
                'bar' => 'foo',
            ],
            'a~b' => 'c',
            'a/b' => 'c',
            'a$b' => 'c',
        ];

        $t = new Transformer($spec);
        $actual = $t($json);

        $this->assertSame($expected, $actual);
    }

    /**
     * Provide basic transform tests.
     *
     * @return array Returns a data provider array.
     */
    public function provideTransformTests(): array {
        $r = [
            'basic' => [['foo' => '/bar'], ['foo' => 'foo']],
            'basic ref' => [['foo' => 'nested/bar'], ['foo' => 'foo']],
            'basic nested' => [['nested' => ['foo' => '/foo']], ['nested' => ['foo' => 'baz']]],
            'escape ~' => [['a' => 'a~0b'], ['a' => 'c']],
            'escape /' => [['a' => 'a~1b'], ['a' => 'c']],
            'escape $' => [['a' => 'a~2b'], ['a' => 'c']],
            'not found' => [['a' => 'xdsd'], []],
            'not found default' => [['a' => ['$ref' => 'xdsd', '$default' => 'b']], ['a' => 'b']],
            'literal' => [['a' => ['$literal' => 'abc']], ['a' => 'abc']],

        ];

        return $r;
    }

    /**
     * @expectedException \Garden\JSON\InvalidSpecException
     */
    public function testInvalidSpec() {
        $t = new Transformer(['foo' => true]);

        $a = $t->transform(['baz']);

    }
}
