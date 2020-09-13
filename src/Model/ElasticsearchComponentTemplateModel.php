<?php

namespace App\Model;

use App\Model\AbstractAppModel;
use App\Traits\MappingsSettingsAliasesModelTrait;

class ElasticsearchComponentTemplateModel extends AbstractAppModel
{
    use MappingsSettingsAliasesModelTrait;

    private $name;

    private $version;

    private $metadata;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata($metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function isSystem(): ?bool
    {
        return '.' == substr($this->getName(), 0, 1);
    }

    public function convert(?array $template): self
    {
        $this->setName($template['name']);
        if (true === isset($template['component_template']['version'])) {
            $this->setVersion($template['component_template']['version']);
        }
        if (true === isset($template['component_template']['template']['settings']) && 0 < count($template['component_template']['template']['settings'])) {
            $this->setSettings($template['component_template']['template']['settings']);
        }
        if (true === isset($template['component_template']['template']['mappings']) && 0 < count($template['component_template']['template']['mappings'])) {
            $this->setMappings($template['component_template']['template']['mappings']);
        }
        if (true === isset($template['component_template']['template']['aliases']) && 0 < count($template['component_template']['template']['aliases'])) {
            $this->setAliases($template['component_template']['template']['aliases']);
        }
        if (true === isset($template['component_template']['_meta']) && 0 < count($template['component_template']['_meta'])) {
            $this->setMetadata($template['component_template']['_meta']);
        }

        return $this;
    }

    public function getJson(): array
    {
        $json = [
            'template' => [],
        ];

        if ($this->getVersion()) {
            $json['version'] = $this->getVersion();
        }

        if ($this->getSettings()) {
            $json['template']['settings'] = $this->getSettings();
        }

        if ($this->getMappings()) {
            $json['template']['mappings'] = $this->getMappings();
        }

        if ($this->getAliases()) {
            $json['template']['aliases'] = $this->getAliases();
        }

        if ($this->getMetadata()) {
            $json['_meta'] = $this->getMetadata();
        }

        if (0 == count($json['template'])) {
            $json['template'] = (object)[];
        }

        return $json;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
