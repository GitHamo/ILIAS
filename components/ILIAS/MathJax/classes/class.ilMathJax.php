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

/**
 * Temporary wrapper for the use of \ILIAS\UI\Component\Legacy\LatexContent
 * The whole MathJax service will be removed in stable ILIAS 11
 * @deprecated - use ILIAS\UI\Component\Legacy\LatexContent instead
 */
class ilMathJax
{
    public const PURPOSE_BROWSER = 'browser';
    public const PURPOSE_EXPORT = 'export';

    protected static ?self $_instance;

    protected function __construct(
        private readonly \ILIAS\UI\Factory $factory,
        private readonly \ILIAS\UI\Renderer $renderer
    ) {
    }

    /**
     * @deprecated - use ILIAS\UI\Component\Legacy\LatexContent instead
     */
    public static function getInstance(): ilMathJax
    {
        global $DIC;
        return self::$_instance ??= new self(
            $DIC->ui()->factory(),
            $DIC->ui()->renderer()
        );
    }

    /**
     * @deprecated - use ILIAS\UI\Component\Legacy\LatexContent instead
     */
    public function setZoomFactor(float $a_factor): ilMathJax
    {
        return $this;
    }

    /**
     * @deprecated - use ILIAS\UI\Component\Legacy\LatexContent instead
     */
    public function init(string $a_purpose = self::PURPOSE_BROWSER): ilMathJax
    {
        return $this;
    }

    /**
     * @deprecated - use ILIAS\UI\Component\Legacy\LatexContent instead
     */
    public function includeMathJax(?ilGlobalTemplateInterface $a_tpl = null): ilMathJax
    {
        return $this;
    }

    /**
     * Replace all tex code within given start and end delimiters in a text
     * @return string    replaced text
     * @deprecated - use ILIAS\UI\Component\Legacy\LatexContent instead
     */
    public function insertLatexImages(string $a_text, ?string $a_start = '[tex]', ?string $a_end = '[/tex]'): string
    {
        // keep the ILIAS delimiters
        if ($a_start === '[tex]' && $a_end === '[/tex]') {
            return $this->renderer->render($this->factory->legacy()->latexContent($a_text));
        }

        // current position to start the search for delimiters
        $cpos = 0;

        // find position of start delimiter
        while (is_int($spos = ilStr::strIPos($a_text, $a_start, $cpos))) {

            // find position of end delimiter
            if (is_int($epos = ilStr::strIPos($a_text, $a_end, $spos + ilStr::strLen($a_start)))) {

                // extract the tex code inside the delimiters
                $tex = ilStr::subStr($a_text, $spos + ilStr::strLen($a_start), $epos - $spos - ilStr::strLen($a_start));

                //wrap in new delimiters
                $replacement = '[tex]' . $tex . '[/tex]';

                // replace delimiters and tex code with prepared code or generated image
                $a_text = ilStr::subStr($a_text, 0, $spos) . $replacement
                    . ilStr::subStr($a_text, $epos + ilStr::strLen($a_end));

                // continue search behind replacement
                $cpos = $spos + ilStr::strLen($replacement);

            } else {
                // end delimiter position not found => stop search
                break;
            }

            if ($cpos >= ilStr::strlen($a_text)) {
                // current position at the end => stop search
                break;
            }
        }

        return $this->renderer->render($this->factory->legacy()->latexContent($a_text));
    }
}
