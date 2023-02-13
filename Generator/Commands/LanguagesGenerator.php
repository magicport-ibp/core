<?php

namespace Apiato\Core\Generator\Commands;

use Apiato\Core\Generator\GeneratorCommand;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Illuminate\Support\Str;

class LanguagesGenerator extends GeneratorCommand implements ComponentsGenerator
{
    /**
     * User required/optional inputs expected to be passed while calling the command.
     * This is a replacement of the `getArguments` function "which reads whenever it's called".
     *
     * @var  array
     */
    public array $inputs = [
    ];
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mp:g:language';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Configuration file for a Container';
    /**
     * The type of class being generated.
     */
    protected string $fileType = 'Translation File';
    /**
     * The structure of the file path.
     */
    protected string $pathStructure = '{module-name}/{section-name}/Languages/en/*';
    /**
     * The structure of the file name.
     */
    protected string $nameStructure = '{file-name}';
    /**
     * The name of the stub file.
     */
    protected string $stubName = 'lang.stub';

    public function getUserInputs(): ?array
    {
        return [
            'path-parameters' => [
                'module-name' => $this->moduleName,
                'section-name' => $this->sectionName,
                'container-name' => $this->containerName,
            ],
            'stub-parameters' => [
                '_module-name' => Str::camel($this->moduleName),
                'module-name' => $this->moduleName,
                '_section-name' => Str::lower($this->sectionName),
                'section-name' => $this->sectionName,
                '_container-name' => Str::camel($this->containerName),
                'container-name' => $this->containerName,
                'class-name' => $this->fileName,
                'file-name' => $this->getDefaultFileName(),
            ],
            'file-parameters' => [
                'file-name' => $this->getDefaultFileName(),
            ],
        ];
    }

    /**
     * Get the default file name for this component to be generated
     */
    public function getDefaultFileName(): string
    {
        return Str::camel($this->moduleName) . '-' . Str::camel($this->containerName);
    }
}
