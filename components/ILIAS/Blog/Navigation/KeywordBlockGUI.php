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

class KeywordBlockGUI
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
        bool $show_inactive = false,
        string $link_template = "",
        int $blpg = 0
    ): string {
        $ctrl = $this->gui->ctrl();
        $lng = $this->domain->lng();

        $keywords = $this->getKeywords($items, $show_inactive, $blpg);
        if ($keywords) {
            $wtpl = new \ilTemplate("tpl.blog_list_navigation_keywords.html", true, true, "components/ILIAS/Blog");

            $max = max($keywords);

            $wtpl->setCurrentBlock("keyword");
            foreach ($keywords as $keyword => $counter) {
                if (!$link_template) {
                    $ctrl->setParameterByClass(\ilObjBlogGUI::class, "kwd", urlencode((string) $keyword));
                    $url = $ctrl->getLinkTargetByClass(\ilObjBlogGUI::class, $list_cmd);
                    $ctrl->setParameterByClass(\ilObjBlogGUI::class, "kwd", "");
                } else {
                    $url = $this->buildExportLink($link_template, "keyword", (string) $keyword);
                }

                $wtpl->setVariable("TXT_KEYWORD", (string) $keyword);
                $wtpl->setVariable("CLASS_KEYWORD", \ilTagging::getRelevanceClass((int) $counter, (int) $max));
                $wtpl->setVariable("URL_KEYWORD", $url);
                $wtpl->parseCurrentBlock();
            }

            return $wtpl->get();
        }
        return "";
    }

    /**
     * @param Posting[][] $items
     */
    protected function getKeywords(
        array $items,
        bool $show_inactive,
        ?int $posting_id = null
    ): array {
        $keywords = array();
        $posting_manager = $this->domain->posting();
        $obj_id = \ilObject::_lookupObjId($this->gui->standardRequest()->getRefId());

        if ($posting_id) {
            foreach ($posting_manager->getKeywords($obj_id, $posting_id) as $keyword) {
                if (isset($keywords[$keyword])) {
                    $keywords[$keyword]++;
                } else {
                    $keywords[$keyword] = 1;
                }
            }
        } else {
            foreach ($items as $month => $month_items) {
                foreach ($month_items as $item) {
                    $item_id = $item->getId();
                    if ($show_inactive || \ilBlogPosting::_lookupActive($item_id, "blp")) {
                        foreach ($posting_manager->getKeywords($obj_id, $item_id) as $keyword) {
                            if (isset($keywords[$keyword])) {
                                $keywords[$keyword]++;
                            } else {
                                $keywords[$keyword] = 1;
                            }
                        }
                    }
                }
            }
        }

        $tmp = array();
        foreach ($keywords as $keyword => $counter) {
            $tmp[] = array("keyword" => $keyword, "counter" => $counter);
        }
        $tmp = \ilArrayUtil::sortArray($tmp, "keyword", "ASC");

        $keywords = array();
        foreach ($tmp as $item) {
            $keywords[(string) $item["keyword"]] = $item["counter"];
        }
        return $keywords;
    }

    protected function buildExportLink(
        string $template,
        string $type,
        string $id
    ): string {
        $blog_export = new \ILIAS\Blog\Export\BlogHtmlExport($this->gui->standardRequest()->getRefId());
        return $blog_export->buildExportLink($template, $type, $id, []);
    }
}
