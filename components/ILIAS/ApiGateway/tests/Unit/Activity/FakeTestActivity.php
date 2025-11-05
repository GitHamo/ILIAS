<?php

declare(strict_types=1);

namespace Tests\Unit\Fixtures;

use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Activities\ActivityType;
use ILIAS\Component\Dependencies\Name;
use ILIAS\Data\Description\Description;
use ILIAS\Data\Description\Factory as DescriptionFactory;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Data\Result;
use ILIAS\Data\Text\SimpleDocumentMarkdown;
use ILIAS\UI\Component\Input\Control\Form\FormInput;

class FakeTestActivity implements Activity
{
    private DataFactory $dataFactory;

    public function __construct(
        private ?ActivityType $type = null,
    ) {
        $this->dataFactory = new DataFactory();
    }

    public function getName(): Name
    {
        return new Name(self::class);
    }

    public function getType(): ActivityType
    {
        return $this->type ?? ActivityType::Query;
    }

    public function getDescription(): SimpleDocumentMarkdown
    {
        return $this->dataFactory->text()->markdown()->simpleDocument("This is an example activity.");
    }

    public function getInputDescription(): FormInput
    {
        //
    }

    public function getOutputDescription(DescriptionFactory $factory): Description
    {
        return $factory->string(
            $this->getDescription()
        );
    }

    public function isAllowedToPerform(int $usr_id, mixed $parameters): bool
    {
        return true;
    }

    public function perform(mixed $parameters): mixed
    {
        return $this->dataFactory->ok("Example activity performed. " . json_encode($parameters));
    }

    /**
     * @param array<string, mixed> $raw_parameters
     */
    public function maybePerformAs(int $usr_id, array $raw_parameters): Result
    {
        if ($this->isAllowedToPerform($usr_id, $raw_parameters)) {
            $results = $this->perform($raw_parameters);

            return $this->dataFactory->ok($results);
        }

        return $this->dataFactory->error('Failed due to permissions.');
    }
}
