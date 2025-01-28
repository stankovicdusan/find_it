<?php

namespace App\Model\Dto;

use App\Validator as MainAssert;

#[MainAssert\Project]
class CreateProjectDto 
{
    public function __construct(
        private readonly string $title,
        private readonly string $key,
        private readonly string $templateId,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTemplateId(): int
    {
        return (int) $this->templateId;
    }
}
