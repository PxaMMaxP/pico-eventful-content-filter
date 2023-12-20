<?php

class EventfulContentFilter extends AbstractPicoPlugin
{
    /**
     * API version used by this plugin
     *
     * @var int
     */
    public const API_VERSION = 2;

    /**
     * Triggered when Pico registers the twig template engine
     *
     * @see Pico::getTwig()
     *
     * @param TwigEnvironment &$twig Twig instance
     */
    public function onTwigRegistered()
    {
        // Zugriff auf Twig-Environment
        $twig = $this->getPico()->getTwig();

        // Neuen Filter hinzufügen
        $twig->addFilter(new Twig\TwigFilter('eventfulContentFilter', array($this, 'eventfulContentFilter')));
    }

    /**
     * Contend filter like the reagular content filter but with the ability to trigger events
     *
     * @see Pico::getTwig()
     *
     * @param string $pageId
     */
    public function eventfulContentFilter($pageId)
    {
        $pico = $this->getPico();
        $pages = &$pico->getPages();
        if (isset($pages[$pageId])) {
            $pageData = $pages[$pageId];
            if (!isset($pageData['content'])) {
                $markdown = $pico->prepareFileContent($pageData['raw_content'], $pageData['meta']);
                $pico->triggerEvent('onContentPrepared', array(&$markdown));
                $pageData['content'] = $pico->parseFileContent($markdown);
                $pico->triggerEvent('onContentParsed', array(&$pageData['content']));
            }
            $pageData['content'] = $this->individualizeFootnotes($pageData['content'], 'u' . str_replace("/", "", $pageId));
            return $pageData['content'];
        }
        return null;
    }

    private function individualizeFootnotes($html, $stringToAdd)
    {
        // Create a DOMDocument and load the HTML code
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Individualize the sup elements and their children
        $supElements = $dom->getElementsByTagName('sup');
        foreach ($supElements as $sup) {
            if ($sup->hasAttribute('id')) {
                $sup->setAttribute('id', $sup->getAttribute('id') . $stringToAdd);
            }
            foreach ($sup->getElementsByTagName('a') as $a) {
                if ($a->hasAttribute('href')) {
                    // Extract the number from the href attribute
                    $href = $a->getAttribute('href');
                    if (preg_match('/#fn:(\d+)/', $href, $matches)) {
                        $number = $matches[1];
                        // Replace the text in the a tag with the extracted number
                        $a->nodeValue = $number;
                    }
                    $a->setAttribute('href', $href . $stringToAdd);
                }
            }
        }

        // Individualize the li elements in the footnotes div
        $divs = $dom->getElementsByTagName('div');
        foreach ($divs as $div) {
            if ($div->getAttribute('class') == 'footnotes') {
                $lis = $div->getElementsByTagName('li');
                foreach ($lis as $li) {
                    if ($li->hasAttribute('id')) {
                        $li->setAttribute('id', $li->getAttribute('id') . $stringToAdd);
                    }
                    foreach ($li->getElementsByTagName('a') as $a) {
                        if ($a->getAttribute('class') == 'footnote-backref' && $a->hasAttribute('href')) {
                            $a->setAttribute('href', $a->getAttribute('href') . $stringToAdd);
                        }
                    }
                }
            }
        }

        // Return the modified HTML code
        return $dom->saveHTML();
    }

}
