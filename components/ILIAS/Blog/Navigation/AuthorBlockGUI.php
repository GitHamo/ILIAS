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

namespace ILIAS\Blog\Navigation;

use ILIAS\Blog\InternalDomainService;
use ILIAS\Blog\InternalGUIService;
use ILIAS\Blog\Posting\Posting;

class AuthorBlockGUI
{
    protected InternalDomainService $domain;
    protected InternalGUIService $gui;

    public function __construct(
        InternalDomainService $domain,
        InternalGUIService $gui
    ) {
        $this->domain = $domain;
        $this->gui = $gui;
    }

    /**
     * @param Posting[][] $items
     */
    public function render(
        array $items,
        string $list_cmd = "render",
        bool $show_inactive = false
    ): string {
        $ctrl = $this->gui->ctrl();
        $lng = $this->domain->lng();

        $authors = array();
        foreach ($items as $month => $month_items) {
            foreach ($month_items as $item) {
                $item_id = $item->getId();
                if (($show_inactive || \ilBlogPosting::_lookupActive($item_id, "blp"))) {
                    $author_id = $item->getAuthor();
                    if ($author_id) {
                        $authors[] = $author_id;
                    }
                    foreach (\ilPageObject::getPageContributors("blp", $item_id) as $editor) {
                        $editor_id = (int) $editor["user_id"];
                        if ($editor_id !== $author_id) {
                            $authors[] = $editor_id;
                        }
                    }
                }
            }
        }

        $authors = array_unique($authors);

        // filter out deleted users
        $authors = array_filter($authors, function ($id) {
            return \ilObject::_lookupType($id) == "usr";
        });

        if (count($authors) > 1) {
            $list = array();
            foreach ($authors as $user_id) {
                if ($user_id) {
                    $ctrl->setParameterByClass(\ilObjBlogGUI::class, "ath", (string) $user_id);
                    $url = $ctrl->getLinkTargetByClass(\ilObjBlogGUI::class, $list_cmd);
                    $ctrl->setParameterByClass(\ilObjBlogGUI::class, "ath", "");

                    $base_name = \ilUserUtil::getNamePresentation($user_id);
                    if (str_starts_with($base_name, "[")) {
                        $name = \ilUserUtil::getNamePresentation($user_id, true);
                        $sort = $name;
                    } else {
                        $name = \ilUserUtil::getNamePresentation(
                            $user_id,
                            true,
                            false,
                            "",
                            false,
                            true,
                            false
                        );
                        $name_arr = \ilObjUser::_lookupName($user_id);
                        $sort = $name_arr["lastname"] . " " . $name_arr["firstname"];
                    }

                    $idx = trim(strip_tags((string) $sort)) . "///" . $user_id;
                    $list[$idx] = array($name, $url);
                }
            }
            ksort($list);

            $wtpl = new \ilTemplate("tpl.blog_list_navigation_authors.html", true, true, "components/ILIAS/Blog");

            $wtpl->setCurrentBlock("author");
            foreach ($list as $author) {
                $wtpl->setVariable("TXT_AUTHOR", $author[0]);
                $wtpl->setVariable("URL_AUTHOR", $author[1]);
                $wtpl->parseCurrentBlock();
            }

            return $wtpl->get();
        }
        return "";
    }
}
