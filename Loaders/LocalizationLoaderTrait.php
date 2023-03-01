<?php

namespace Apiato\Core\Loaders;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait LocalizationLoaderTrait
{
    public function loadLocalsFromContainers($containerPath): void
    {
        $containerLocaleDirectory = $containerPath . '/Languages';
        $containerName = basename($containerPath);
        $pathParts = explode(DIRECTORY_SEPARATOR , $containerPath);
        $sectionName = $pathParts[count($pathParts) - 2];

        $this->loadLocals($containerLocaleDirectory , $containerName , $sectionName);
    }

    public function loadLocalsFromSection($containerPath): void
    {
        $containerLocaleDirectory = $containerPath . '/Languages';

        $pathParts = explode(DIRECTORY_SEPARATOR , $containerPath);
        $moduleName = $pathParts[count($pathParts) - 2];
        $sectionName = $pathParts[count($pathParts) - 1];

        $this->loadLocals($containerLocaleDirectory , $sectionName , $moduleName);
    }


    private function loadLocals($directory , $containerName = null , $sectionName = null): void
    {
        if ( File::isDirectory($directory) ) {

            $this->loadTranslationsFrom($directory , $this->buildLocaleNamespace($sectionName , $containerName));
            $this->loadJsonTranslationsFrom($directory);
        }
    }

    private function buildLocaleNamespace(?string $sectionName , ?string $containerName): string
    {

        return $sectionName ? (Str::camel($sectionName) . '@' . Str::camel($containerName)) : Str::camel($containerName);
    }

    public function loadLocalsFromShip(): void
    {
        $shipLocaleDirectory = base_path('app/MagicPort/Ship/Languages');
        $this->loadLocals($shipLocaleDirectory , 'ship');
    }
}
