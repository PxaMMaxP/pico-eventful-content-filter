<?php
declare(strict_types=1);

use Twig\Environment as TwigEnvironment;
use Twig\Filter as TwigFilter;

class EventfulContentFilter extends AbstractPicoPlugin
{
    /**
     * API version used by this plugin
     *
     * @var int
     */
    public const API_VERSION = 4;

    /**
     * Triggered when Pico registers the twig template engine
     *
     * @see Pico::getTwig()
     *
     * @param TwigEnvironment &$twig Twig instance
     */
    public function onTwigRegistered(TwigEnvironment &$twig): void
    {
        $twig->addFilter(new TwigFilter(
            'eventfulContentFilter',
            array($this, 'eventfulContentFilter'),
            [ 'is_safe' => [ 'html' ] ]
        ));
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
            $pageData = &$pages[$pageId];
            if (!isset($pageData['content'])) {
                $markdown = $pico->prepareFileContent($pageData['raw_content'], $pageData['meta'], $page);
                $pico->triggerEvent('onContentPrepared', [ &$markdown ]);
                $pageData['content'] = $pico->parseFileContent($markdown);
                $pico->triggerEvent('onContentParsed', [ &$pageData['content'] ]);
            }
            return $pageData['content'];
        }
        return null;
    }
}
