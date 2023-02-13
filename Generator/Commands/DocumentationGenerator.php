<?php

namespace Apiato\Core\Generator\Commands;

use Apiato\Core\Generator\GeneratorCommand;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class DocumentationGenerator extends GeneratorCommand implements ComponentsGenerator
{
    /**
     * User required/optional inputs expected to be passed while calling the command.
     * This is a replacement of the `getArguments` function "which reads from the console whenever it's called".
     *
     * @var  array
     */
    public array $inputs = [
        ['model', null, InputOption::VALUE_OPTIONAL, 'The model this action is for.'],
        ['stub', null, InputOption::VALUE_OPTIONAL, 'The stub file to load for this generator.'],
        ['ui', null, InputOption::VALUE_OPTIONAL, 'The user-interface to generate the Action for.'],

    ];
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mp:g:documentation';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Documentation  for a Module';
    /**
     * The type of class being generated.
     */
    protected string $fileType = 'Documentation';
    /**
     * The structure of the file path.
     */
    protected string $pathStructure = '{module-name}/Documentation/*';
    /**
     * The structure of the file name.
     */
    protected string $nameStructure = '{file-name}';
    /**
     * The name of the stub file.
     */
    protected string $stubName = 'actions/generic.stub';


    public function handle()
    {


        $this->core = $this->checkParameterOrConfirm('core', 'Is this a core module?', false) ;
        $this->moduleName = ucfirst($this->checkParameterOrAsk('module', 'Enter the name of the Module', ));

        $this->moduleName = $this->removeSpecialChars($this->moduleName);
        $this->userData = $this->getUserInputs();


        if ($this->userData === null) {
            // The user skipped this step
            return;
        }

        $fil = [
            'config' => [
                'stub-directory' => 'documentation/config.stub',
                'file-name' => 'config',
                'ext'   => 'php',
            ],
            'header' => [
                'stub-directory' => 'documentation/header.template.en.stub',
                'file-name' => 'shared/header.template.en',
                'ext'   => 'md',
            ],
            'controller' => [
                'stub-directory' => 'documentation/controller.stub',
                'file-name' => 'UI/WEB/Controllers/DocumentationController',
                'ext'   => 'php',
            ],
            'request' => [
                'stub-directory' => 'documentation/request.stub',
                'file-name' => 'UI/WEB/Requests/DocumentationRequest',
                'ext'   => 'php',
            ],
            'router' => [
                'stub-directory' => 'documentation/router.stub',
                'file-name' => 'UI/WEB/Routes/DocumentationRoute',
                'ext'   => 'php',
            ],
        ];

        //Define Module in App\MagicPort\Modules or App\Modules
        $this->checkIsCoreFolder() ;

        foreach ($fil as $key => $type)
        {
            //Create Config File
            $this->parsedFileName = $this->parseFileStructure($this->nameStructure, $fil[$key]);

            $this->filePath = $this->getFilePath($this->parsePathStructure($this->pathStructure, $this->userData['path-parameters']),$fil[$key]['ext']);

            if (!$this->fileSystem->exists($this->filePath)) {
                $this->stubName = $fil[$key]['stub-directory'];
                $this->stubContent = $this->getStubContent();




                $this->renderedStubContent = $this->parseStubContent($this->stubContent, $this->userData['stub-parameters']);

                $this->generateFile($this->filePath, $this->renderedStubContent);

            }

        }

        $this->printFinishedMessage($this->fileType);







    }

    public function getUserInputs(): ?array
    {


        $moduleName = $this->moduleName;

        return [
            'path-parameters' => [
                'module-name' => $this->moduleName,


            ],
            'stub-parameters' => [
                'module-name' => $moduleName,
                '_module-name' => Str::lower($moduleName),
                '__module-name' => Str::ucfirst($moduleName),
                'camel-module-name' => Str::camel($moduleName),
                'kebab-module-name' => Str::kebab($moduleName),

            ],
            'file-parameters' => [
                'file-name' => 'test',
            ],
        ];
    }

    /**
     * Get the default file name for this component to be generated
     */
    public function getDefaultFileName(): string
    {
        return 'config';
    }
}
