<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\JSON;

/**
 * A class that transforms data based on a specification.
 */
final class Transformer {
    /**
     * @var array The transform spec.
     */
    private $spec;

    /**
     * Transformer constructor.
     *
     * @param array $spec The transformation spec.
     */
    public function __construct(array $spec) {
        $this->spec = $spec;
    }

    /**
     * Transform an array of data.
     *
     * @param mixed $data The data to transform.
     * @return mixed Returns the transformed data.
     * @throws InvalidSpecException Throws an exception if the spec has an error.
     */
    public function transform($data) {
        $r = $this->transformInternal($this->spec, $data);

        return $r;
    }

    /**
     * An alias for `transform()`.
     *
     * @param mixed $data The data to transform.
     * @return mixed Returns the transformed data.
     * @throws InvalidSpecException Throws an exception if the spec has an error.
     */
    public function __invoke($data) {
        return $this->transform($data);
    }

    /**
     * Transform a spec node.
     *
     * @param array $spec The spec node to transform.
     * @param array $data The data to transform.
     * @param string $path The current spec path being transformed.
     * @return mixed Returns the transformed data.
     * @throws InvalidSpecException Throws an exception if the spec has an error.
     */
    private function transformInternal(array $spec, array $data, string $path = '/') {
        $result = [];

        foreach ($spec as $key => $value) {
            if (substr($key, 0, 1) === '$') {
                // This is a control expression; resolve it.
                $result = $this->resolveControlExpression($key, $spec, $data, $path);
                break;
            } elseif (is_string($value) || is_int($value)) {
                // This is a reference; look it up.
                $r = $this->resolveReference($value, $data, $data, $found);
                if ($found) {
                    $result[$key] = $r;
                }
            } elseif (is_array($value)) {
                $result[$key] = $this->transformInternal($value, $data, $path.static::escapeRef($key).'/');
            } else {
                $subpath = $path.static::escapeRef($key);

                throw new InvalidSpecException("Invalid spec value at $subpath.");
            }
        }

        return $result;
    }

    /**
     * Resolve a JSON reference.
     *
     * @param string $ref The reference to resolve.
     * @param array $context The current data context to lookup.
     * @param array $root The root data context for absolute references.
     * @param bool $found Set to **true** if the reference was found or **false** otherwise.
     * @return mixed Returns the value at the reference.
     */
    private function resolveReference(string $ref, array $context, array $root, bool &$found = null) {
        $found = true;

        if ($ref === '') {
            return $context;
        } elseif ($ref[0] === '/') {
            $ref = substr($ref, 1);
            $context = $root;
        }

        $parts = self::explodeRef($ref);

        $result = $context;
        foreach ($parts as $key) {
            if (array_key_exists($key, $result)) {
                $result = $result[$key];
            } else {
                $found = false;
                return null;
            }
        }

        return $result;
    }

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
    public static function unescapeRef(string $str): string {
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
     * Resolve a control expression.
     *
     * @param string $expr The expression to resolve.
     * @param array $spec The spec node where the expression was found.
     * @param array $data The data to lookup.
     * @param string $path The current spec path being looked at.
     * @return mixed Returns the resolved expression.
     * @throws InvalidSpecException Throws an exception if the spec has an error.
     */
    private function resolveControlExpression(string $expr, array $spec, array $data, string $path) {
        switch ($expr) {
            case '$ref':
            case '$default':
                $result = $this->resolveReference($spec['$ref'] ?? null, $data, $data, $found);
                if (!$found) {
                    $result = $spec['$default'] ?? null;
                }
                return $result;
            case '$literal':
                return $spec['$literal'];
            default:
                throw new InvalidSpecException("Invalid control expression \"$expr\" at $path");
        }
    }
}
