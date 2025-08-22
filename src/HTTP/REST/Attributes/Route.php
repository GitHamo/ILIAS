<?php

declare(strict_types=1);

namespace ILIAS\HTTP\REST\Attributes;

use Attribute;
use ILIAS\HTTP\REST\SchemaValidator;

#[Attribute(Attribute::TARGET_METHOD)]
final class Route
{
    /**
     * @param string|string[] $methods     */
    public function __construct(
        public string|array $methods,
        public string $path,
        public ?SchemaValidator $schemaValidator = null,
    ) {
        $this->methods = (array) $methods;
    }
}
