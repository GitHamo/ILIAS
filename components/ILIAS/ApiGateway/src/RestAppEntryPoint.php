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

namespace ILIAS\ApiGateway;

use ilContext;
use ILIAS\ApiGateway\Application\Factory\WebAppFactory;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use ILIAS\Init\AllModernComponents;

class RestAppEntryPoint extends AllModernComponents
{
    public function __construct(
        protected WebAppFactory $webAppFactory,
        // extra dependencies for ILIAS Legacy Initialisation
        protected \ILIAS\Refinery\Factory $refinery_factory,
        protected \ILIAS\Data\Factory $data_factory,
        protected \ILIAS\UI\Factory $ui_factory,
        protected \ILIAS\UI\Renderer $ui_renderer,
        protected \ILIAS\UI\Implementation\Component\Counter\Factory $ui_factory_counter,
        protected \ILIAS\UI\Implementation\Component\Button\Factory $ui_factory_button,
        protected \ILIAS\UI\Implementation\Component\Listing\Factory $ui_factory_listing,
        protected \ILIAS\UI\Implementation\Component\Listing\Workflow\Factory $ui_factory_listing_workflow,
        protected \ILIAS\UI\Implementation\Component\Listing\CharacteristicValue\Factory $ui_factory_listing_characteristic_value,
        protected \ILIAS\UI\Implementation\Component\Listing\Entity\Factory $ui_factory_listing_entity,
        protected \ILIAS\UI\Implementation\Component\Image\Factory $ui_factory_image,
        protected \ILIAS\UI\Implementation\Component\Player\Factory $ui_factory_player,
        protected \ILIAS\UI\Implementation\Component\Panel\Factory $ui_factory_panel,
        protected \ILIAS\UI\Implementation\Component\Modal\Factory $ui_factory_modal,
        protected \ILIAS\UI\Implementation\Component\Dropzone\Factory $ui_factory_dropzone,
        protected \ILIAS\UI\Implementation\Component\Popover\Factory $ui_factory_popover,
        protected \ILIAS\UI\Implementation\Component\Divider\Factory $ui_factory_divider,
        protected \ILIAS\UI\Implementation\Component\Link\Factory $ui_factory_link,
        protected \ILIAS\UI\Implementation\Component\Dropdown\Factory $ui_factory_dropdown,
        protected \ILIAS\UI\Implementation\Component\Item\Factory $ui_factory_item,
        /** @phpstan-ignore-next-line */
        protected \ILIAS\UI\Implementation\Component\Viewcontrol\Factory $ui_factory_viewcontrol,
        protected \ILIAS\UI\Implementation\Component\Chart\Factory $ui_factory_chart,
        protected \ILIAS\UI\Implementation\Component\Input\Factory $ui_factory_input,
        protected \ILIAS\UI\Implementation\Component\Table\Factory $ui_factory_table,
        protected \ILIAS\UI\Implementation\Component\MessageBox\Factory $ui_factory_messagebox,
        protected \ILIAS\UI\Implementation\Component\Card\Factory $ui_factory_card,
        protected \ILIAS\UI\Implementation\Component\Layout\Factory $ui_factory_layout,
        protected \ILIAS\UI\Implementation\Component\Layout\Page\Factory $ui_factory_layout_page,
        protected \ILIAS\UI\Implementation\Component\Layout\Alignment\Factory $ui_factory_layout_alignment,
        /** @phpstan-ignore-next-line */
        protected \ILIAS\UI\Implementation\Component\Maincontrols\Factory $ui_factory_maincontrols,
        protected \ILIAS\UI\Implementation\Component\Tree\Factory $ui_factory_tree,
        protected \ILIAS\UI\Implementation\Component\Tree\Node\Factory $ui_factory_tree_node,
        protected \ILIAS\UI\Implementation\Component\Menu\Factory $ui_factory_menu,
        protected \ILIAS\UI\Implementation\Component\Symbol\Factory $ui_factory_symbol,
        protected \ILIAS\UI\Implementation\Component\Toast\Factory $ui_factory_toast,
        protected \ILIAS\UI\Implementation\Component\Legacy\Factory $ui_factory_legacy,
        protected \ILIAS\UI\Implementation\Component\Launcher\Factory $ui_factory_launcher,
        protected \ILIAS\UI\Implementation\Component\Entity\Factory $ui_factory_entity,
        protected \ILIAS\UI\Implementation\Component\Panel\Listing\Factory $ui_factory_panel_listing,
        protected \ILIAS\UI\Implementation\Component\Panel\Secondary\Factory $ui_factory_panel_secondary,
        protected \ILIAS\UI\Implementation\Component\Modal\InterruptiveItem\Factory $ui_factory_interruptive_item,
        protected \ILIAS\UI\Implementation\Component\Chart\ProgressMeter\Factory $ui_factory_progressmeter,
        protected \ILIAS\UI\Implementation\Component\Chart\Bar\Factory $ui_factory_bar,
        /** @phpstan-ignore-next-line */
        protected \ILIAS\UI\Implementation\Component\Input\Viewcontrol\Factory $ui_factory_input_viewcontrol,
        protected \ILIAS\UI\Implementation\Component\Input\Container\ViewControl\Factory $ui_factory_input_container_viewcontrol,
        protected \ILIAS\UI\Implementation\Component\Table\Column\Factory $ui_factory_table_column,
        protected \ILIAS\UI\Implementation\Component\Table\Factory $ui_factory_table_action,
        /** @phpstan-ignore-next-line */
        protected \ILIAS\UI\Implementation\Component\Maincontrols\Slate\Factory $ui_factory_maincontrols_slate,
        /** @phpstan-ignore-next-line */
        protected \ILIAS\UI\Implementation\Component\Symbol\icon\Factory $ui_factory_symbol_icon,
        protected \ILIAS\UI\Implementation\Component\Symbol\Glyph\Factory $ui_factory_symbol_glyph,
        /** @phpstan-ignore-next-line */
        protected \ILIAS\UI\Implementation\Component\Symbol\avatar\Factory $ui_factory_symbol_avatar,
        protected \ILIAS\UI\Implementation\Component\Input\Container\Form\Factory $ui_factory_input_container_form,
        protected \ILIAS\UI\Implementation\Component\Input\Container\Filter\Factory $ui_factory_input_container_filter,
        protected \ILIAS\UI\Implementation\Component\Input\Field\Factory $ui_factory_input_field,
        protected \ILIAS\UI\Implementation\Component\Prompt\Factory $ui_prompt_factory,
        protected \ILIAS\UI\Implementation\Component\Prompt\State\Factory $ui_prompt_state_factory,
        protected \ILIAS\UI\Implementation\Component\Progress\Factory $ui_progress_factory,
        protected \ILIAS\UI\Implementation\Component\Progress\State\Factory $ui_progress_state_factory,
        protected \ILIAS\UI\Implementation\Component\Progress\State\Bar\Factory $ui_progress_state_bar_factory,
        protected \ILIAS\UI\Implementation\Component\Input\UploadLimitResolver $ui_upload_limit_resolver,
        protected \ILIAS\Setup\AgentFinder $setup_agent_finder,
        protected \ILIAS\UI\Implementation\Component\Navigation\Factory $ui_factory_navigation,
    ) {
        parent::__construct(
            $refinery_factory,
            $data_factory,
            $ui_factory,
            $ui_renderer,
            $ui_factory_counter,
            $ui_factory_button,
            $ui_factory_listing,
            $ui_factory_listing_workflow,
            $ui_factory_listing_characteristic_value,
            $ui_factory_listing_entity,
            $ui_factory_image,
            $ui_factory_player,
            $ui_factory_panel,
            $ui_factory_modal,
            $ui_factory_dropzone,
            $ui_factory_popover,
            $ui_factory_divider,
            $ui_factory_link,
            $ui_factory_dropdown,
            $ui_factory_item,
            $ui_factory_viewcontrol,
            $ui_factory_chart,
            $ui_factory_input,
            $ui_factory_table,
            $ui_factory_messagebox,
            $ui_factory_card,
            $ui_factory_layout,
            $ui_factory_layout_page,
            $ui_factory_layout_alignment,
            $ui_factory_maincontrols,
            $ui_factory_tree,
            $ui_factory_tree_node,
            $ui_factory_menu,
            $ui_factory_symbol,
            $ui_factory_toast,
            $ui_factory_legacy,
            $ui_factory_launcher,
            $ui_factory_entity,
            $ui_factory_panel_listing,
            $ui_factory_panel_secondary,
            $ui_factory_interruptive_item,
            $ui_factory_progressmeter,
            $ui_factory_bar,
            $ui_factory_input_viewcontrol,
            $ui_factory_input_container_viewcontrol,
            $ui_factory_table_column,
            $ui_factory_table_action,
            $ui_factory_maincontrols_slate,
            $ui_factory_symbol_icon,
            $ui_factory_symbol_glyph,
            $ui_factory_symbol_avatar,
            $ui_factory_input_container_form,
            $ui_factory_input_container_filter,
            $ui_factory_input_field,
            $ui_prompt_factory,
            $ui_prompt_state_factory,
            $ui_progress_factory,
            $ui_progress_state_factory,
            $ui_progress_state_bar_factory,
            $ui_upload_limit_resolver,
            $setup_agent_finder,
            $ui_factory_navigation,
        );
    }

    #[\Override]
    public function getName(): string
    {
        return self::class;
    }

    #[\Override]
    public function enter(): int
    {
        ilContext::init(ilContext::CONTEXT_REST);

        parent::enter();

        $this->webAppFactory->create(ServiceProtocol::REST)->run();

        return 0;
    }
}
