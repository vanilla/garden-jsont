<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\JSON;


trait ReferenceResolverTrait {
    /**
     * Escape a JSON reference field.
     *
     * @param string $field The reference field to escape.
     * @return string Returns an escaped reference.
     */
    private static function escapeRef(string $field): string {
        return str_replace(['~', '/', '$'], ['~0', '~1', '~2'], $field);
    }

    /**
     * Unescape a JSON reference segment.
     *
     * @param string $str The segment to unescapeRef.
     * @return string Returns the unescaped string.
     */
    private static function unescapeRef(string $str): string {
        return str_replace(['~2', '~1', '~0'], ['$', '/', '~'], $str);
    }

    /**
     * Explode a references into its individual parts.
     *
     * @param string $ref A JSON reference.
     * @return string[] The individual parts of the reference.
     */
    private static function explodeRef(string $ref): array {
        return array_map([self::class, 'unescapeRef'], explode('/', $ref));
    }

    /**
     * Resolve a JSON reference.
     *
     * @param int|string $ref The reference to resolve.
     * @param mixed $context The current data context to lookup.
     * @param mixed $root The root data context for absolute references.
     * @param bool $found Set to **true** if the reference was found or **false** otherwise.
     * @return mixed Returns the value at the reference.
     */
    private function resolveReference($ref, $context, $root, bool &$found = null) {
        $found = true;

        if ($ref === '') {
            return $context;
        } elseif ($ref === '/') {
            return $root;
        } elseif (is_int($ref)) {
            return $context[$ref];
        } elseif ($ref[0] === '/') {
            $ref = substr($ref, 1);
            $context = $root;
        }

        $parts = self::explodeRef($ref);
        $result = $context;
        foreach ($parts as $key) {
            if (!is_array($result)) {
                $found = false;
                return null;
            } elseif (array_key_exists($key, $result)) {
                $result = $result[$key];
            } else {
                $found = false;
                return null;
            }
        }

        return $result;
    }
}
