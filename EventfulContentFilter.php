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

            return $pageData['content'];
        }
        return null;
    }
}
