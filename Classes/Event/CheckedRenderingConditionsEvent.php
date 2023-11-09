<?php

declare(strict_types=1);

namespace Sitegeist\Translatelabels\Event;

final class CheckedRenderingConditionsEvent
{
    private bool $showTranslationLabels;
    private string $labelKey;
    private string $extensionName;

    public function __construct(
        bool $showTranslationLabels,
        string $labelKey,
        string $extensionName
    ) {
        $this->showTranslationLabels = $showTranslationLabels;
        $this->labelKey = $labelKey;
        $this->extensionName = $extensionName;
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
}
