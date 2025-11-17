<?php

declare(strict_types=1);

namespace ILIAS\ApiGateway\Examples;

use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Activities\ActivityType;
use ILIAS\Component\Dependencies\Name;
use ILIAS\Data\Description\Description;
use ILIAS\Data\Description\Factory as DescriptionFactory;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Data\Result;
use ILIAS\Data\Text\SimpleDocumentMarkdown;
use ILIAS\UI\Component\Input\Control\Form\FormInput; // this should be pointed to the correct namespace Input\Container\Form\FormInput
use Override;

class ExampleActivity implements Activity
{
    private const int ADMIN_ID = 6;

    public function __construct(
        private DataFactory $dataFactory,
    ) {}

    #[Override]
    public function getName(): Name
    {
        return new Name(self::class);
    }

    #[Override]
    public function getType(): ActivityType
    {
        return ActivityType::Query;
    }

    #[Override]
    public function getDescription(): SimpleDocumentMarkdown
    {
        return $this->createSimpleMarkdown("This is an example activity.");
    }

    #[Override]
    public function getInputDescription(): FormInput
    {
        // no input validation implemented
    }

    #[Override]
    public function getOutputDescription(DescriptionFactory $factory): Description
    {
        // no output validation implemented
    }

    #[Override]
    public function isAllowedToPerform(int $usr_id, mixed $parameters): bool
    {
        return $usr_id === self::ADMIN_ID ? true : false;
    }

    #[Override]
    public function perform(mixed $parameters): mixed
    {
        return [
            'message' => 'Hello World',
        ];
    }

    #[Override]
    public function maybePerformAs(int $usr_id, array $raw_parameters): Result
    {
        if ($this->isAllowedToPerform($usr_id, $raw_parameters)) {
            $results = $this->perform($raw_parameters);

            return $this->dataFactory->ok($results);
        }

        return $this->dataFactory->error('Failed due to permissions.');
    }

    private function createSimpleMarkdown(string $text): SimpleDocumentMarkdown
    {
        return $this->dataFactory->text()->markdown()->simpleDocument($text);
    }
}
