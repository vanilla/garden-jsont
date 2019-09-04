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
     * @var string|array The transform spec.
     */
    private $spec;

    /**
     * Transformer constructor.
     *
     * @param array|string $spec The transformation spec.
     */
    public function __construct($spec) {
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
        $r = $this->transformInternal($this->spec, $data, $data, '/');

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
     * @param array|array $spec The spec node to transform.
     * @param array $data The data to transform.
     * @param array $root The root of the data from the first call to `transform()`.
     * @param string $path The current spec path being transformed.
     * @return mixed Returns the transformed data.
     * @throws InvalidSpecException Throws an exception if the spec has an error.
     */
    private function transformInternal($spec, $data, $root, string $path) {
        if (is_string($spec) || is_int($spec)) {
            return $this->resolveReference($spec, $data, $root, $found);
        }

        $result = [];
        foreach ($spec as $key => $value) {
            if (substr($key, 0, 1) === '$') {
                // This is a control expression; resolve it.
                $result = $this->resolveControlExpression($key, $spec, $data, $root, $path);
                break;
            } elseif (is_string($value) || is_int($value)) {
                // This is a reference; look it up.
                $r = $this->resolveReference($value, $data, $root, $found);
                if ($found) {
                    $result[$key] = $r;
                }
            } elseif (is_array($value)) {
                $result[$key] = $this->transformInternal($value, $data, $root, $path . static::escapeRef($key) . '/');
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
     * @param mixed $context The current data context to lookup.
     * @param mixed $root The root data context for absolute references.
     * @param bool $found Set to **true** if the reference was found or **false** otherwise.
     * @return mixed Returns the value at the reference.
     */
    private function resolveReference(string $ref, $context, $root, bool &$found = null) {
        $found = true;

        if ($ref === '') {
            return $context;
        } elseif ($ref === '/') {
            return $root;
        } elseif ($ref[0] === '/') {
            $ref = substr($ref, 1);
            $context = $root;
        }

        if (!is_array($context)) {
            $found = false;
            return null;
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
     * @param array $root The root of the data for absolute references.
     * @param string $path The current spec path being looked at.
     * @return mixed Returns the resolved expression.
     * @throws InvalidSpecException Throws an exception if the spec has an error.
     */
    private function resolveControlExpression(string $expr, array $spec, array $data, array $root, string $path) {
        switch ($expr) {
            case '$ref':
            case '$default':
                $result = $this->resolveReference($spec['$ref'] ?? null, $data, $root, $found);
                if (!$found) {
                    $result = $spec['$default'] ?? null;
                }
                return $result;
            case '$each':
            case '$item':
            case '$index':
                $result = $this->resolveEach($spec, $data, $root, $path);
                return $result;
            case '$literal':
                return $spec['$literal'];
            default:
                throw new InvalidSpecException("Invalid control expression \"$expr\" at $path");
        }
    }

    private function resolveEach(array $spec, array $data, array $root, string $path) {
        if (!array_key_exists('$each', $spec)) {
            throw new InvalidSpecException("Missing key \$each at $path.");
        }
        if (!array_key_exists('$item', $spec)) {
            throw new InvalidSpecException("Missing key \$item at $path.");
        }

        $each = $this->resolveReference($spec['$each'] ?? null, $data, $root, $found);
        $itemSpec = $spec['$item'];
        $keySpec = $spec['$key'] ?? '$key';

        if (!$found) {
            return null;
        }

        $result = [];
        $index = 0;
        foreach ($each as $i => $item) {
            $subPath = $path.static::escapeRef($i).'/';

            if ($keySpec === '$key') {
                $key = $i;
            } elseif ($keySpec === '$index') {
                $key = $index;
            } else {
                $key = $this->transformInternal($keySpec, $item, $root, $subPath);
            }


            $result[$key] = $this->transformInternal($itemSpec, $item, $root, $subPath);
            $index++;
        }
        return $result;
    }
}
