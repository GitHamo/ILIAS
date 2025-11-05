<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\User\Profile\Fields\Custom;

use ILIAS\User\Context;
use ILIAS\Language\Language;
use ILIAS\UI\Component\Input\Field\Factory as FieldFactory;
use ILIAS\UI\Component\Input\Container\Form\FormInput;
use ILIAS\Refinery\Factory as Refinery;

class Select implements Type
{
    public function getLabel(Language $lng): string
    {
        return $lng->txt('udf_type_select');
    }

    public function getAdditionalEditFormInputs(
        Language $lng,
        FieldFactory $ff,
        Refinery $refinery,
        ?string $data
    ): ?FormInput {
        $parsed_data = $this->parseData($data);
        return $ff->group([
            'allow_multiple' => $ff->checkbox($lng->txt('multiple_selection'))
                ->withValue($parsed_data['allow_multiple']),
            'options' => $ff->tag($lng->txt('udf_select_options'), [])
                ->withValue($parsed_data['options'])
        ])->withAdditionalTransformation(
            $refinery->custom()->transformation(
                static fn(array $vs): string => json_encode([
                    'allow_multiple' => $vs['allow_multiple'],
                    'options' => $vs['options']
                ])
            )
        );
    }

    public function getLegacyInput(
        Language $lng,
        Context $context,
        array $user_value,
        string $label,
        ?string $data
    ): \ilFormPropertyGUI {
        $parsed_data = $this->parseData($data);
        if (!$parsed_data['allow_multiple']) {
            $value = isset($user_value[0])
                ? array_search($user_value[0], $parsed_data['options'])
                : false;

            $input = new \ilSelectInputGUI($label);
            $input->setOptions(['' => $lng->txt('please_select')] + $parsed_data['options']);
            $input->setValue($value !== false ? $value : '');
            return $input;
        }

        $input = new \ilMultiSelectInputGUI($label);
        $input->setOptions($parsed_data['options']);
        $input->setValue(
            array_reduce(
                $user_value,
                function (array $c, string $v) use ($parsed_data): array {
                    $value = array_search($v, $parsed_data['options']);
                    if ($value === false) {
                        return $c;
                    }

                    $c[] = $value;
                    return $c;
                },
                []
            )
        );
        return $input;


    }

    public function prepareUserInputForStorage(mixed $input, ?string $data): array
    {
        $options = $this->parseData($data)['options'];
        if (!is_array($input)) {
            $input = [$input];
        }

        return array_reduce(
            $input,
            function (array $c, string|int $v) use ($options): array {
                $value = $options[$v] ?? null;
                if ($value !== null) {
                    $c[] = $value;
                }
                return $c;
            },
            []
        );
    }

    private function parseData(?string $data): array
    {
        if ($data === null) {
            return [
                'allow_multiple' => false,
                'options' => []
            ];
        }

        $values = json_decode($data, true) ?? unserialize($data, ['allowed_classes' => false]) ?? [];

        return [
            'allow_multiple' => $values['allow_multiple'] ?? false,
            'options' => $values['options'] ?? $values
        ];
    }
}
