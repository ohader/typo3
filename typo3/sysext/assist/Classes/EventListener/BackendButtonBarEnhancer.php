<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Assist\EventListener;

use TYPO3\CMS\Assist\Domain\Model\Assistant;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Assist\Service\DataSetService;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Adds assist buttons to the backend button bar.
 */
#[AsEventListener('typo3/cms-assist/backend-button-bar-enhancer')]
final readonly class BackendButtonBarEnhancer
{
    public function __construct(
        private IconFactory $iconFactory,
        private PageRenderer $pageRenderer,
        private DataSetService $dataSetService,
        private ComponentFactory $componentFactory,
        private AssistantRegistry $assistantRegistry,
    ) {}

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->getAttribute('route');
        $assistants = $this->resolveAssistantsMatchingContext($route);
        if ($assistants === []) {
            return;
        }

        $this->pageRenderer->loadJavaScriptModule('@typo3/assist/assistant-trigger.js');

        $buttons = $event->getButtons();
        $nextButtonGroup = $this->resolveHighestButtonGroup(ButtonBar::BUTTON_POSITION_LEFT, $buttons) + 1;
        $buttons[ButtonBar::BUTTON_POSITION_LEFT][$nextButtonGroup][] = $this->buildAssistButton($assistants);
        $event->setButtons($buttons);
    }

    /**
     * @param list<Assistant> $assistants
     */
    private function buildAssistButton(array $assistants): ButtonInterface
    {
        $assistButton = $this->componentFactory->createDropDownButton()
            ->setLabel('Ask AI')
            ->setTitle('Ask AI')
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-wand-sparkles', IconSize::SMALL));

        // @todo forward current call context
        // data-assist-template :== meta|media|alt
        foreach ($assistants as $assistant) {
            $assistButton->addItem(
                $this->componentFactory->createDropDownItem()
                    ->setLabel($this->getLanguageService()->sL($assistant->labelDomain . ':default'))
                    ->setHref('#')
                    ->setAttributes([
                        'class' => 't3js-assist-trigger-item',
                        ...$this->dataSetService->forAssistant($assistant)
                    ])
            );
        }
        return $assistButton;
    }

    private function resolveHighestButtonGroup(string $position, array $buttons): int
    {
        $groups = array_keys($buttons[$position] ?? []);
        return max([0, ...$groups]);
    }

    /**
     * @return list<Assistant>
     */
    private function resolveAssistantsMatchingContext(Route $route): array
    {
        return $this->assistantRegistry->getAssistantsByTriggerRoute($route->getPath());
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
