<?php

namespace Apiato\Core\Generator\Commands;

use Apiato\Core\Generator\GeneratorCommand;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ContainerFullRouterGenerator extends GeneratorCommand implements ComponentsGenerator
{
    /**
     * User required/optional inputs expected to be passed while calling the command.
     * This is a replacement of the `getArguments` function "which reads whenever it's called".
     *
     * @var  array
     */
    public array $inputs = [
        [ 'docversion' , null , InputOption::VALUE_OPTIONAL , 'The version of all endpoints to be generated (1, 2, ...)' ] ,
        [ 'verb' , null , InputOption::VALUE_OPTIONAL , 'The HTTP verb of the endpoint (GET, POST, ...)' ] ,
        [ 'doctype' , null , InputOption::VALUE_OPTIONAL , 'The type of all endpoints to be generated (private, public)' ] ,
        [ 'url' , null , InputOption::VALUE_OPTIONAL , 'The base URI of all endpoints (/stores, /cars, ...)' ] ,
        [ 'controllertype' , null , InputOption::VALUE_OPTIONAL , 'The controller type (SAC, MAC)' ] ,
        [ 'events' , null , InputOption::VALUE_OPTIONAL , 'Generate Events for this Container?' ] ,
        [ 'listeners' , null , InputOption::VALUE_OPTIONAL , 'Generate Event Listeners for Events of this Container?' ] ,
        [ 'tests' , null , InputOption::VALUE_OPTIONAL , 'Generate Tests for this Container?' ] ,

    ];
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mp:g:route:full';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Route with Request, Controller, Action and etc.';
    /**
     * The type of class being generated.
     */
    protected string $fileType = 'Route';
    /**
     * The structure of the file path.
     */
    protected string $pathStructure = '{module-name}/{section-name}/{container-name}/*';
    /**
     * The structure of the file name.
     */
    protected string $nameStructure = '{file-name}';
    /**
     * The name of the stub file.
     */
    protected string $stubName = 'routes/generic.stub';

    public function getUserInputs(): ?array
    {
        $ui = 'api';

        // Module name as inputted and lower
        $moduleName = $this->moduleName;
        $_moduleName = Str::lower($this->moduleName);

        // section name as inputted and lower
        $sectionName = $this->sectionName;
        $_sectionName = Str::lower($this->sectionName);

        // container name as inputted and lower
        $containerName = $this->containerName;
        $_containerName = Str::lower($this->containerName);

        // name of the model (singular and plural)
        $model = $this->containerName;
        $models = Pluralizer::plural($model);



        // create the default routes for this container
        $this->printInfoMessage('Generating Default Route');
        $version = $this->checkParameterOrAsk('docversion' , 'Enter the version for  API endpoint (integer)' , 1);
        $doctype = $this->checkParameterOrAsk('doctype' , 'Select the type for API endpoint' , Str::lower($this->moduleName) );
        $verb = Str::upper($this->checkParameterOrAsk('verb' , 'Enter the HTTP verb of this endpoint (GET, POST,...)', 'GET'));

        // get the URI and remove the first trailing slash
        $url = Str::lower($this->checkParameterOrAsk('url' , 'Enter the base URI for all API endpoints (foo/bar/{id})' , Str::kebab($models)));
        $url = ltrim($url , '/');

        $controllertype = Str::lower($this->checkParameterOrChoice('controllertype' , 'Select the controller type (Single or Multi Action Controller)' , [ 'SAC' , 'MAC' ] , 0));

        $generateEvents = $this->checkParameterOrConfirm('events' , 'Do you want to generate the corresponding CRUD Events for this Container?' , false);
        $generateListeners = false;
        if ( $generateEvents ) {
            $generateListeners = $this->checkParameterOrConfirm('listeners' , 'Do you want to generate the corresponding Event Listeners for this Events?' , false);
        }

        $generateTests = $this->checkParameterOrConfirm('tests' , 'Do you want to generate the corresponding Tests for this Container?' , false);

        $generateEvents ?: $this->printInfoMessage('Generating CRUD Events');
        $generateListeners ?: $this->printInfoMessage('Generating Event Listeners');
        $generateTests ?: $this->printInfoMessage('Generating Tests for Container');
        $this->printInfoMessage('Generating Requests for Routes');
        $this->printInfoMessage('Generating Default Actions');
        $this->printInfoMessage('Generating Default Tasks');
        $this->printInfoMessage('Generating Default Controller/s');

        $routes = [
            [
                'stub'           => 'generic' ,
                'name'           =>  $this->fileName . $models ,
                'operation'      =>  Str::camel($this->fileName) . $models ,
                'verb'           =>  $verb ,
                'url'            =>  $url ,
                'action'         => $this->fileName . $models . 'Action' ,
                'request'        => $this->fileName . $models . 'Request' ,
                'task'           => $this->fileName . $models . 'Task' ,
                'unittest'       => $this->fileName . $models . 'TaskTest' ,
                'functionaltest' => $this->fileName . $models . 'Test' ,
                'event'          => $models . 'ListedEvent' ,
                'controller'     => $this->fileName . $models . 'Controller' ,
                'file-name'      => $this->fileName . $models ,
            ] ,
//            [
//                'stub'           => 'Find' ,
//                'name'           => 'Find' . $model . 'ById' ,
//                'operation'      => 'find' . $model . 'ById' ,
//                'verb'           => 'GET' ,
//                'url'            => $url . '/{id}' ,
//                'action'         => 'Find' . $model . 'ById' . 'Action' ,
//                'request'        => 'Find' . $model . 'ById' . 'Request' ,
//                'task'           => 'Find' . $model . 'ById' . 'Task' ,
//                'unittest'       => 'Find' . $model . 'ById' . 'TaskTest' ,
//                'functionaltest' => 'Find' . $model . 'ById' . 'Test' ,
//                'event'          => $model . 'FoundById' . 'Event' ,
//                'controller'     => 'Find' . $model . 'ById' . 'Controller' ,
//            ] ,
//            [
//                'stub'           => 'Create' ,
//                'name'           => 'Create' . $model ,
//                'operation'      => 'create' . $model ,
//                'verb'           => 'POST' ,
//                'url'            => $url ,
//                'action'         => 'Create' . $model . 'Action' ,
//                'request'        => 'Create' . $model . 'Request' ,
//                'task'           => 'Create' . $model . 'Task' ,
//                'unittest'       => 'Create' . $model . 'TaskTest' ,
//                'functionaltest' => 'Create' . $model . 'Test' ,
//                'event'          => $model . 'CreatedEvent' ,
//                'controller'     => 'Create' . $model . 'Controller' ,
//            ] ,
//            [
//                'stub'           => 'Update' ,
//                'name'           => 'Update' . $model ,
//                'operation'      => 'update' . $model ,
//                'verb'           => 'PATCH' ,
//                'url'            => $url . '/{id}' ,
//                'action'         => 'Update' . $model . 'Action' ,
//                'request'        => 'Update' . $model . 'Request' ,
//                'task'           => 'Update' . $model . 'Task' ,
//                'unittest'       => 'Update' . $model . 'TaskTest' ,
//                'functionaltest' => 'Update' . $model . 'Test' ,
//                'event'          => $model . 'UpdatedEvent' ,
//                'controller'     => 'Update' . $model . 'Controller' ,
//            ] ,
//            [
//                'stub'           => 'Delete' ,
//                'name'           => 'Delete' . $model ,
//                'operation'      => 'delete' . $model ,
//                'verb'           => 'DELETE' ,
//                'url'            => $url . '/{id}' ,
//                'action'         => 'Delete' . $model . 'Action' ,
//                'request'        => 'Delete' . $model . 'Request' ,
//                'task'           => 'Delete' . $model . 'Task' ,
//                'unittest'       => 'Delete' . $model . 'TaskTest' ,
//                'functionaltest' => 'Delete' . $model . 'Test' ,
//                'event'          => $model . 'DeletedEvent' ,
//                'controller'     => 'Delete' . $model . 'Controller' ,
//            ] ,
        ];

        foreach ( $routes as $route ) {
            $this->call('mp:g:request' , [
                '--core'      => $this->core ,
                '--module'    => $moduleName ,
                '--section'   => $sectionName ,
                '--container' => $containerName ,
                '--file'      => $route['request'] ,
                '--ui'        => $ui ,
                '--stub'      => $route['stub'] ,

            ]);

            $this->call('mp:g:action' , [
                '--core'      => $this->core ,
                '--module'    => $moduleName ,
                '--section'   => $sectionName ,
                '--container' => $containerName ,
                '--file'      => $route['action'] ,
                '--ui'        => $ui ,
                '--model'     => $model ,
                '--stub'      => 'route' ,
                '--file-name' => $route['file-name'] ,
            ]);

            $this->call('mp:g:task' , [
                '--core'      => $this->core ,
                '--module'    => $moduleName ,
                '--section'   => $sectionName ,
                '--container' => $containerName ,
                '--file'      => $route['task'] ,
                '--model'     => $model ,
                '--stub'      => $route['stub'] ,
                '--event'     => $generateEvents ? $route['event'] : false ,
            ]);

            if ( $generateEvents ) {
                $this->call('mp:g:event' , [
                    '--core'      => $this->core ,
                    '--module'    => $moduleName ,
                    '--section'   => $sectionName ,
                    '--container' => $containerName ,
                    '--file'      => $route['event'] ,
                    '--model'     => $model ,
                    '--stub'      => $route['stub'] ,
                    '--listener'  => $generateListeners ,
                ]);

                // create the EventServiceProvider for the container
                $this->printInfoMessage('Generating EventServiceProvider');
                $this->call('mp:g:provider' , [
                    '--core'      => $this->core ,
                    '--module'    => $moduleName ,
                    '--section'   => $sectionName ,
                    '--container' => $containerName ,
                    '--file'      => 'EventServiceProvider' ,
                    '--stub'      => 'eventserviceprovider' ,
                ]);
            }

            if ( $generateTests ) {
                $this->call('mp:g:test:unit' , [
                    '--core'      => $this->core ,
                    '--module'    => $moduleName ,
                    '--section'   => $sectionName ,
                    '--container' => $containerName ,
                    '--file'      => $route['unittest'] ,
                    '--model'     => $model ,
                    '--stub'      => $route['stub'] ,
                    '--event'     => $generateEvents ? $route['event'] : false ,
                ]);

                $this->call('mp:g:test:unit' , [
                    '--core'      => $this->core ,
                    '--module'    => $moduleName ,
                    '--section'   => $sectionName ,
                    '--container' => $containerName ,
                    '--file'      => $model . 'FactoryTest' ,
                    '--model'     => $model ,
                    '--stub'      => 'factory' ,
                    '--event'     => false ,
                ]);

                $this->call('mp:g:test:unit' , [
                    '--core'      => $this->core ,
                    '--module'    => $moduleName ,
                    '--section'   => $sectionName ,
                    '--container' => $containerName ,
                    '--file'      => $models . 'MigrationTest' ,
                    '--model'     => $model ,
                    '--stub'      => 'migration' ,
                    '--event'     => false ,
                    '--tablename' => Str::snake(Pluralizer::plural($containerName)) ,
                ]);

                $this->call('mp:g:test:functional' , [
                    '--core'      => $this->core ,
                    '--module'    => $moduleName ,
                    '--section'   => $sectionName ,
                    '--container' => $containerName ,
                    '--file'      => $route['functionaltest'] ,
                    '--model'     => $model ,
                    '--ui'        => $ui ,
                    '--stub'      => $route['stub'] ,
                    '--url'       => $route['url'] ,
                ]);
            }

            if ( $controllertype === 'sac' ) {
                $this->call('mp:g:route' , [
                    '--core'       => $this->core ,
                    '--module'     => $moduleName ,
                    '--section'    => $sectionName ,
                    '--container'  => $containerName ,
                    '--file'       => $route['name'] ,
                    '--ui'         => $ui ,
                    '--operation'  => $route['operation'] ,
                    '--doctype'    => $doctype ,
                    '--docversion' => $version ,
                    '--url'        => $route['url'] ,
                    '--verb'       => $route['verb'] ,
                    '--controller' => $route['controller'] ,
                ]);

                $this->call('mp:g:controller' , [
                    '--core'      => $this->core ,
                    '--module'    => $moduleName ,
                    '--section'   => $sectionName ,
                    '--container' => $containerName ,
                    '--file'      => $route['controller'] ,
                    '--ui'        => $ui ,
                    '--stub'      => 'route' ,
                    '--file-name' => $route['file-name'] ,
                ]);
            } else {
                $this->call('mp:g:route' , [
                    '--core'       => $this->core ,
                    '--module'     => $moduleName ,
                    '--section'    => $sectionName ,
                    '--container'  => $containerName ,
                    '--file'       => $route['name'] ,
                    '--ui'         => $ui ,
                    '--operation'  => $route['operation'] ,
                    '--doctype'    => $doctype ,
                    '--docversion' => $version ,
                    '--url'        => $route['url'] ,
                    '--verb'       => $route['verb'] ,
                    '--controller' => 'Controller' ,
                ]);
            }
        }

        if ( $controllertype === 'mac' ) {
            $this->printInfoMessage('Generating Controller to wire everything together');
            $this->call('mp:g:controller' , [
                '--core'      => $this->core ,
                '--module'    => $moduleName ,
                '--section'   => $sectionName ,
                '--container' => $containerName ,
                '--file'      => 'Controller' ,
                '--ui'        => $ui ,
                '--stub'      => 'crud' ,
            ]);
        }



        return null;

    }

//    /**
//     * Get the default file name for this component to be generated
//     */
//    public function getDefaultFileName(): string
//    {
//        return 'composer';
//    }

    public function getDefaultFileExtension($ext = 'php'): string
    {
        return 'json';
    }


    public function getDefaultFileName(): string
    {
        return 'GetList'; // TODO: Change the autogenerated stub
    }
}
