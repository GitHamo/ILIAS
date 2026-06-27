<?php

/* Copyright (c) 1998-2026 ILIAS open source, Extended GPL, see docs/LICENSE */

declare(strict_types=1);

namespace ILIAS\Blog\Posting\Service;

use ILIAS\Blog\InternalDataService;
use ILIAS\Blog\InternalDomainService;
use ILIAS\Blog\InternalGUIService;

/**
 * GUI service for blog postings
 */
class GUIService
{
    public function __construct(
        protected InternalDataService $data,
        protected InternalDomainService $domain,
        protected InternalGUIService $gui
    ) {
    }

    public function postingGUI(
        int $a_node_id,
        ?object $a_access_handler = null,
        int $a_id = 0,
        int $a_old_nr = 0,
        bool $a_enable_public_notes = true,
        bool $a_may_contribute = true,
        int $a_style_sheet_id = 0
    ): \ilBlogPostingGUI {
        return new \ilBlogPostingGUI(
            $a_node_id,
            $a_access_handler,
            $a_id,
            $a_old_nr,
            $a_enable_public_notes,
            $a_may_contribute,
            $a_style_sheet_id
        );
    }

    /**
     * Get first text paragraph of page
     */
    public function getSnippet(
        int $a_id,
        bool $a_truncate = false,
        int $a_truncate_length = 500,
        string $a_truncate_sign = "...",
        bool $a_include_picture = false,
        int $a_picture_width = 144,
        int $a_picture_height = 144,
        ?string $a_export_directory = null
    ): string {
        $bpgui = $this->postingGUI(0, null, $a_id);
        return $bpgui->getSnippet(
            $a_truncate,
            $a_truncate_length,
            $a_truncate_sign,
            $a_include_picture,
            $a_picture_width,
            $a_picture_height,
            $a_export_directory
        );
    }
}
