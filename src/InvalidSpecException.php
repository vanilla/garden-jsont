<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\JSON;

final class InvalidSpecException extends \Exception {

    /**
     * InvalidSpecException constructor.
     *
     * @param string $message The error message.
     */
    public function __construct(string $message) {
        parent::__construct($message, 500);
    }
}
