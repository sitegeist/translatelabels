<?php

declare(strict_types=1);

namespace Sitegeist\Translatelabels\Event;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

final class CheckedRenderingConditionsEvent
{
    private bool $showTranslationLabels;
    private string $labelKey;
    private string $extensionName;
    private ?RenderingContextInterface $renderingContext;

    public function __construct(
        bool $showTranslationLabels,
        string $labelKey,
        string $extensionName,
        RenderingContextInterface $renderingContext = null
    ) {
        $this->showTranslationLabels = $showTranslationLabels;
        $this->labelKey = $labelKey;
        $this->extensionName = $extensionName;
        $this->renderingContext = $renderingContext;
    }

    public function getShowTranslationLabels(): bool
    {
        return $this->showTranslationLabels;
    }

    public function setShowTranslationLabels(bool $showTranslationLabels): void
    {
        $this->showTranslationLabels = $showTranslationLabels;
    }

    public function getLabelKey(): string
    {
        return $this->labelKey;
    }

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    public function getRenderingContext(): ?RenderingContextInterface
    {
        return $this->renderingContext;
    }
}
