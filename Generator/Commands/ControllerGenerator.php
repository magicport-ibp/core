<?php

namespace Apiato\Core\Generator\Commands;

use Apiato\Core\Generator\GeneratorCommand;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ControllerGenerator extends GeneratorCommand implements ComponentsGenerator
{
    /**
     * The options which can be passed to the command. All options are optional. You do not need to pass the
     * "--container" and "--file" options, as they are globally handled. Just use the options which are specific to
     * this generator.
     *
     * @var  array
     */
    public array $inputs = [
        [ 'ui' , null , InputOption::VALUE_OPTIONAL , 'The user-interface to generate the Controller for.' ] ,
        [ 'stub' , null , InputOption::VALUE_OPTIONAL , 'The stub file to load for this generator.' ] ,

    ];
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mp:g:controller';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a controller for a container';
    /**
     * The type of class being generated.
     */
    protected string $fileType = 'Controller';
    /**
     * The structure of the file path.
     */
    protected string $pathStructure = '{module-name}/{section-name}/{container-name}/UI/{user-interface}/Controllers/*';
    /**
     * The structure of the file name.
     */
    protected string $nameStructure = '{file-name}';
    /**
     * The name of the stub file.
     */
    protected string $stubName = 'controllers/generic.stub';

    public function getUserInputs(): ?array
    {
        $ui = Str::lower($this->checkParameterOrChoice('ui' , 'Select the UI for the controller' , [ 'API' , 'WEB' ] , 0));

        $stub = Str::lower(
            $this->checkParameterOrChoice(
                'stub' ,
                'Select the Stub you want to load' ,
                [ 'Generic' , 'CRUD' , 'Create' , 'Delete' , 'Find' , 'GetAll' , 'Update' ] ,
                0
            )
        );

        // Load a new stub-file based on the users choice
        $this->stubName = 'controllers/' . $ui . '/' . $stub . '.stub';

        $basecontroller = Str::ucfirst($ui) . 'Controller';

        // Name of the model (singular and plural)
        $model = $this->containerName;
        $models = Pluralizer::plural($model);

        $entity = Str::lower($model);
        $entities = Pluralizer::plural($entity);

        return [
            'path-parameters' => [
                'module-name'    => $this->moduleName ,
                'section-name'   => $this->sectionName ,
                'container-name' => $this->containerName ,
                'user-interface' => Str::upper($ui) ,
            ] ,
            'stub-parameters' => [
                '_module-name'       => Str::lower($this->moduleName) ,
                '__module-name'      => Str::camel($this->moduleName) ,
                'module-name'        => $this->moduleName ,
                '_section-name'      => Str::lower($this->sectionName) ,
                '__section-name'     => Str::camel($this->sectionName) ,
                'section-name'       => $this->sectionName ,
                '_container-name'    => Str::lower($this->containerName) ,
                '__container-name'   => Str::camel($this->containerName) ,
                'container-name'     => $this->containerName ,
                '__translation-name' => Str::camel($this->containerName) ,
                'class-name'         => $this->fileName ,
                'user-interface'     => Str::upper($ui) ,
                'base-controller'    => $basecontroller ,
                'method-name'        => Str::camel($this->option('file-name')) ,
                'file-name'          => $this->option('file-name') ,

                'model'    => $model ,
                'models'   => $models ,
                'entity'   => $entity ,
                'entities' => $entities ,
            ] ,
            'file-parameters' => [
                'file-name' => $this->fileName ,
            ] ,
        ];
    }

    public function getDefaultFileName(): string
    {
        return 'Controller';
    }
}
