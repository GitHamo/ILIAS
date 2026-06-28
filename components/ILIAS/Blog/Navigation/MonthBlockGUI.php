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

class MonthBlockGUI
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
        string $posting_cmd = "preview",
        ?string $link_template = null,
        bool $show_inactive = false,
        int $blpg = 0
    ): string {
        $ctrl = $this->gui->ctrl();
        $lng = $this->domain->lng();
        $settings = $this->domain->blogSettings()->getByObjId(
            \ilObject::_lookupObjId($this->gui->standardRequest()->getRefId())
        );

        // gather page active status
        foreach ($items as $month => $postings) {
            foreach (array_keys($postings) as $id) {
                $active = \ilBlogPosting::_lookupActive($id, "blp");
                if (!$show_inactive && !$active) {
                    unset($items[$month][$id]);
                }
            }
            if (!count($items[$month])) {
                unset($items[$month]);
            }
        }

        // list month (incl. postings)
        if ($settings->getNavMode() === \ilObjBlog::NAV_MODE_LIST || $link_template) {
            $max_months = $settings->getNavModeListMonths();

            $wtpl = new \ilTemplate("tpl.blog_list_navigation_by_date.html", true, true, "components/ILIAS/Blog");

            $ctrl->setParameterByClass(\ilObjBlogGUI::class, "blpg", "");

            $counter = $mon_counter = $last_year = 0;
            foreach ($items as $month => $postings) {
                if (!$link_template && $max_months && $mon_counter >= $max_months) {
                    break;
                }

                $add_year = false;
                $year = substr((string) $month, 0, 4);
                if (!$last_year || $year != $last_year) {
                    $add_year = true;
                    $last_year = $year;
                }

                $mon_counter++;

                $month_name = \ilCalendarUtil::_numericMonthToString((int) substr((string) $month, 5));
                if (!$link_template) {
                    $ctrl->setParameterByClass(\ilObjBlogGUI::class, "bmn", $month);
                    $month_url = $ctrl->getLinkTargetByClass(\ilObjBlogGUI::class, $list_cmd);
                } else {
                    $month_url = $this->buildExportLink($link_template, "list", (string) $month);
                }

                if ($mon_counter <= $settings->getNavModeListMonthsWithPostings()) {
                    if ($add_year) {
                        $wtpl->setCurrentBlock("navigation_year_details");
                        $wtpl->setVariable("YEAR", $year);
                        $wtpl->parseCurrentBlock();
                    }

                    foreach ($postings as $id => $posting) {
                        $counter++;
                        $caption = $posting->getTitle();

                        if (!$link_template) {
                            $ctrl->setParameterByClass(\ilBlogPostingGUI::class, "bmn", $month);
                            $ctrl->setParameterByClass(\ilBlogPostingGUI::class, "blpg", (string) $id);
                            $url = $ctrl->getLinkTargetByClass(\ilBlogPostingGUI::class, $posting_cmd);
                        } else {
                            $url = $this->buildExportLink($link_template, "posting", (string) $id);
                        }

                        if (!$posting->isActive()) {
                            $wtpl->setVariable("NAV_ITEM_DRAFT", $lng->txt("blog_draft"));
                        } elseif ($settings->getApproval() && !$posting->isApproved()) {
                            $wtpl->setVariable("NAV_ITEM_APPROVAL", $lng->txt("blog_needs_approval"));
                        }

                        $wtpl->setCurrentBlock("navigation_item");
                        $wtpl->setVariable("NAV_ITEM_URL", $url);
                        $wtpl->setVariable("NAV_ITEM_CAPTION", $caption);
                        $wtpl->parseCurrentBlock();
                    }

                    $wtpl->setCurrentBlock("navigation_month_details");
                    $wtpl->setVariable("NAV_MONTH", $month_name);
                    $wtpl->setVariable("URL_MONTH", $month_url);
                    $wtpl->parseCurrentBlock();
                }
                // summarized month
                else {
                    if ($add_year) {
                        $wtpl->setCurrentBlock("navigation_year");
                        $wtpl->setVariable("YEAR", $year);
                        $wtpl->parseCurrentBlock();
                    }

                    $wtpl->setCurrentBlock("navigation_month");
                    $wtpl->setVariable("MONTH_NAME", $month_name);
                    $wtpl->setVariable("URL_MONTH", $month_url);
                    $wtpl->setVariable("MONTH_COUNT", (string) count($postings));
                    $wtpl->parseCurrentBlock();
                }
            }
            if (!$link_template) {
                $ctrl->setParameterByClass(\ilObjBlogGUI::class, "bmn", null);
                $url = $ctrl->getLinkTargetByClass(\ilObjBlogGUI::class, $list_cmd);
            } else {
                $url = "index.html";
            }

            $wtpl->setVariable(
                "STARTING_PAGE",
                $this->gui->ui()->renderer()->render(
                    $this->gui->ui()->factory()->link()->standard(
                        $lng->txt("blog_starting_page"),
                        $url
                    )
                )
            );
            $ctrl->setParameterByClass(\ilObjBlogGUI::class, "bmn", $this->gui->standardRequest()->getMonth());
            $ctrl->setParameterByClass(\ilBlogPostingGUI::class, "bmn", "");
            return $wtpl->get();
        }
        // single month
        else {
            $wtpl = new \ilTemplate("tpl.blog_list_navigation_month.html", true, true, "components/ILIAS/Blog");

            $ctrl->setParameterByClass(\ilObjBlogGUI::class, "blpg", "");

            $month_options = array();
            foreach ($items as $month => $postings) {
                $month_name = $this->gui->presentation()->util()->getMonthPresentation((string) $month);

                $month_options[(string) $month] = $month_name;

                if ($month == $this->gui->standardRequest()->getMonth()) {
                    if (!$link_template) {
                        $ctrl->setParameterByClass(\ilObjBlogGUI::class, "bmn", (string) $month);
                        $month_url = $ctrl->getLinkTargetByClass(\ilObjBlogGUI::class, $list_cmd);
                    } else {
                        $month_url = $this->buildExportLink($link_template, "list", (string) $month);
                    }

                    foreach ($postings as $id => $posting) {
                        $caption = $posting->getTitle();

                        if (!$link_template) {
                            $ctrl->setParameterByClass(\ilBlogPostingGUI::class, "bmn", (string) $month);
                            $ctrl->setParameterByClass(\ilBlogPostingGUI::class, "blpg", (string) $id);
                            $url = $ctrl->getLinkTargetByClass(\ilBlogPostingGUI::class, $posting_cmd);
                        } else {
                            $url = $this->buildExportLink($link_template, "posting", (string) $id);
                        }

                        if (!$posting->isActive()) {
                            $wtpl->setVariable("NAV_ITEM_DRAFT", $lng->txt("blog_draft"));
                        } elseif ($settings->getApproval() && !$posting->isApproved()) {
                            $wtpl->setVariable("NAV_ITEM_APPROVAL", $lng->txt("blog_needs_approval"));
                        }

                        $wtpl->setCurrentBlock("navigation_item");
                        $wtpl->setVariable("NAV_ITEM_URL", $url);
                        $wtpl->setVariable("NAV_ITEM_CAPTION", $caption);
                        $wtpl->parseCurrentBlock();
                    }

                    $wtpl->setCurrentBlock("navigation_month_details");
                    if ($blpg > 0) {
                        $wtpl->setVariable("NAV_MONTH", $month_name);
                        $wtpl->setVariable("URL_MONTH", $month_url);
                    }
                    $wtpl->parseCurrentBlock();
                }
            }

            if ($blpg === 0) {
                $wtpl->setCurrentBlock("option_bl");
                foreach ($month_options as $value => $caption) {
                    $wtpl->setVariable("OPTION_VALUE", $value);
                    $wtpl->setVariable("OPTION_CAPTION", $caption);
                    if ($value == $this->gui->standardRequest()->getMonth()) {
                        $wtpl->setVariable("OPTION_SEL", ' selected="selected"');
                    }
                    $wtpl->parseCurrentBlock();
                }

                $wtpl->setVariable("FORM_ACTION", $ctrl->getFormActionByClass(\ilObjBlogGUI::class, $list_cmd));
            }
        }
        $ctrl->setParameterByClass(\ilObjBlogGUI::class, "bmn", $this->gui->standardRequest()->getMonth());
        $ctrl->setParameterByClass(\ilBlogPostingGUI::class, "bmn", "");
        return $wtpl->get();
    }

    protected function buildExportLink(
        string $template,
        string $type,
        string $id
    ): string {
        $blog_export = new \ILIAS\Blog\Export\BlogHtmlExport($this->gui->standardRequest()->getRefId());
        // Note: this might need adjustment since the original used $this->getKeywords(false)
        // For now we assume keywords are not needed for these links or handled elsewhere
        return $blog_export->buildExportLink($template, $type, $id, []);
    }
}
