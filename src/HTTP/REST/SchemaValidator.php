<?php
declare(strict_types=1);

namespace ILIAS\HTTP\REST;

interface SchemaValidator
{
    /**
     * This method should return an array of validation rules.
     * The keys are the field names and the values are the validation rules.
     * For example:
     * [
     *     'field1' => 'required|string|max:255',
     *     'field2' => ['type' => 'integer', 'minimum' => 0],
     *     'field3' => ['type' => 'array', 'items => ['type' => 'string']],
     * ]
     * The rules can be simple strings or more complex arrays depending on the validation requirements.
     * 
     * @return array<string, string|array<string, mixed>>
     */
    public function getRules(): array;
}
