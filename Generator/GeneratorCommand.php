<?php

namespace Apiato\Core\Generator;

use Apiato\Core\Exceptions\GeneratorErrorException;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Apiato\Core\Generator\Traits\FileSystemTrait;
use Apiato\Core\Generator\Traits\FormatterTrait;
use Apiato\Core\Generator\Traits\ParserTrait;
use Apiato\Core\Generator\Traits\PrinterTrait;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

abstract class GeneratorCommand extends Command
{
    use ParserTrait;
    use PrinterTrait;
    use FileSystemTrait;
    use FormatterTrait;

    /**
     * Root directory of all sections
     *
     * @var string
     */
    private const MODULES = 'app/Modules';

    private const CORE_MODULES = 'app/MagicPort/Modules';

    /**
     * Relative path for the stubs (relative to this directory / file)
     *
     * @var string
     */
    private const STUB_PATH = 'Stubs/*';

    /**
     * Relative path for the custom stubs (relative to the app/Ship directory!)
     */
    private const CUSTOM_STUB_PATH = 'Generators/CustomStubs/*';

    /**
     * Default section name
     *
     * @var string
     */
    private const DEFAULT_SECTION_NAME = 'AppSection';

    /**
     * @var string
     */
    protected string $filePath;

    /**
     * @var string Module on Core or Modules
     */
    protected bool $core = false;

    /**
     * @var string the name of the module to generate the stubs
     */
    protected string $moduleName;

    /**
     * @var string the name of the section to generate the stubs
     */
    protected string $sectionName;

    /**
     * @var string the name of the container to generate the stubs
     */
    protected string $containerName;

    /**
     * @var string The name of the file to be created (entered by the user)
     */
    protected string $fileName;

    protected $userData;

    protected $parsedFileName;

    protected $stubContent;

    protected $renderedStubContent;

    protected IlluminateFilesystem $fileSystem;

    private array $defaultInputs = [
        ['core', null, InputOption::VALUE_OPTIONAL, 'The name of the module'],
        ['module', null, InputOption::VALUE_OPTIONAL, 'The name of the module'],
        ['section', null, InputOption::VALUE_OPTIONAL, 'The name of the section'],
        ['container', null, InputOption::VALUE_OPTIONAL, 'The name of the container'],
        ['file', null, InputOption::VALUE_OPTIONAL, 'The name of the file'],
        ['file-name', null, InputOption::VALUE_OPTIONAL, 'The file name without any other charters.'],
        ['skip', null, InputOption::VALUE_OPTIONAL, 'The file name without any other charters.'],
    ];

    public function __construct(IlluminateFilesystem $fileSystem)
    {
        parent::__construct();

        $this->fileSystem = $fileSystem;
    }

    /**
     * @void
     *
     * @throws GeneratorErrorException|FileNotFoundException
     */
    public function handle()
    {


        $this->validateGenerator($this);
        $this->core = $this->checkParameterOrConfirm('core', 'Is this a core module?', false) ;
        $this->moduleName = ucfirst($this->checkParameterOrAsk('module', 'Enter the name of the Module', ));
        $this->sectionName = ucfirst($this->checkParameterOrAsk('section', 'Enter the name of the Section', ));
        $this->containerName = ucfirst($this->checkParameterOrAsk('container', 'Enter the name of the Container'));

        if ($this->option('skip') == true)
        {
            $this->fileName =  $this->getDefaultFileName();

        }
        else{
            $this->fileName = $this->checkParameterOrAsk('file', 'Enter the name of the ' . $this->fileType . ' file', $this->getDefaultFileName());

        }
        // Now fix the section, container and file name
        $this->moduleName = $this->removeSpecialChars($this->moduleName);
        $this->sectionName = $this->removeSpecialCharsAndAddSuffix($this->sectionName)  ;
        $this->containerName = $this->removeSpecialChars($this->containerName);
        if (!($this->fileType === 'Configuration')) {
            $this->fileName = $this->removeSpecialChars($this->fileName);
        }

        // And we are ready to start
        $this->printStartedMessage($this->moduleName . ':' .$this->sectionName . ':' . $this->containerName, $this->fileName);

        // Get user inputs
        $this->userData = $this->getUserInputs();

        if ($this->userData === null) {
            // The user skipped this step
            return;
        }

        $this->userData = $this->sanitizeUserData($this->userData);

        // Get the actual path of the output file as well as the correct filename
        $this->parsedFileName = $this->parseFileStructure($this->nameStructure, $this->userData['file-parameters']);
        $this->filePath = $this->getFilePath($this->parsePathStructure($this->pathStructure, $this->userData['path-parameters']));

        if (!$this->fileSystem->exists($this->filePath)) {
            // Prepare stub content
            $this->stubContent = $this->getStubContent();


            //Define Module in App\MagicPort\Modules or App\Modules
           $this->checkIsCoreFolder() ;


            $this->renderedStubContent = $this->parseStubContent($this->stubContent, $this->userData['stub-parameters']);

            $this->generateFile($this->filePath, $this->renderedStubContent);

            $this->printFinishedMessage($this->fileType);
        }

        // Exit the command successfully
        return 0;
    }

    /**
     * @param $generator
     *
     * @throws GeneratorErrorException
     */
    private function validateGenerator($generator): void
    {
        if (!$generator instanceof ComponentsGenerator) {
            throw new GeneratorErrorException(
                'Your component maker command should implement ComponentsGenerator interface.'
            );
        }
    }

    /**
     * Checks if the param is set (via CLI), otherwise asks the user for a value
     *
     * @param $param
     * @param $question
     * @param string|null $default
     *
     * @return mixed
     */
    protected function checkParameterOrAsk($param, $question, ?string $default = null): mixed
    {
        // Check if we already have a param set
        $value = $this->option($param);
        if ($value === null) {
            // There was no value provided via CLI, so ask the user…
            $value = $this->ask($question, $default);
        }

        return $value;
    }

    /**
     * Get the default file name for this component to be generated
     */
    protected function getDefaultFileName(): string
    {
        return 'Default' . Str::ucfirst($this->fileType);
    }

    /**
     * Removes "special characters" from a string
     * @param $str
     * @return string
     */
    protected function removeSpecialChars($str): string
    {
        // remove everything that is NOT a character or digit
        return preg_replace('/[^A-Za-z0-9]/', '', $str);
    }

    protected function checkIsCoreFolder()
    {
        //Define Module in App\MagicPort\Modules or App\Modules
        if ($this->core)
        {
            $this->userData['stub-parameters']['module-name'] = 'App\\MagicPort\\Modules\\' . $this->userData['stub-parameters']['module-name'];
        }
        else{
            $this->userData['stub-parameters']['module-name'] = 'App\\Modules\\' . $this->userData['stub-parameters']['module-name'];

        }
    }


    protected function removeSpecialCharsAndAddSuffix($str): string
    {
        // remove everything that is NOT a character or digit
        $string = preg_replace('/[^A-Za-z0-9]/', '', $str);

        if (str_ends_with($string, 'Section')) {
            return $string;
        }
        else {
            return $string . 'Section';
        }

    }

    /**
     * Checks, if the data from the generator contains path, stub and file-parameters.
     * Adds empty arrays, if they are missing
     *
     * @param $data
     * @return mixed
     */
    private function sanitizeUserData($data): mixed
    {

        if (!array_key_exists('path-parameters', $data)) {
            $data['path-parameters'] = [];
        }

        if (!array_key_exists('stub-parameters', $data)) {
            $data['stub-parameters'] = [];
        }

        if (!array_key_exists('file-parameters', $data)) {
            $data['file-parameters'] = [];
        }

        return $data;
    }

    protected function getFilePath($path,$ext = 'php'): string
    {

        $root = $this->core ? self::CORE_MODULES : self::MODULES;
        // Complete the missing parts of the path
        $path = base_path() . '/' .
            str_replace('\\', '/', $root . '/' . $path) . '.' . $this->getDefaultFileExtension($ext);

        // Try to create directory
        $this->createDirectory($path);

        // Return full path
        return $path;
    }

    /**
     * Get the default file extension for the file to be created.
     */
    protected function getDefaultFileExtension($ext = 'php'): string
    {
        return $ext;
    }

    /**
     * @return  string
     * @throws FileNotFoundException
     */
    protected function getStubContent(): string
    {
        // Check if there is a custom file that overrides the default stubs
        $path = app_path() . '/MagicPort/Ship/' . self::CUSTOM_STUB_PATH;
        $file = str_replace('*', $this->stubName, $path);

        // Check if the custom file exists
        if (!$this->fileSystem->exists($file)) {
            // It does not exist - so take the default file!
            $path = __DIR__ . '/' . self::STUB_PATH;
            $file = str_replace('*', $this->stubName, $path);
        }

        // Now load the stub
        return $this->fileSystem->get($file);
    }

    /**
     * Get all the console command arguments, from the components. The default arguments are prepended
     */
    protected function getOptions(): array
    {
        return array_merge($this->defaultInputs, $this->inputs);
    }

    /**
     * @param      $arg
     * @param bool $trim
     *
     * @return array|string|null
     */
    protected function getInput($arg, bool $trim = true): array|string|null
    {
        return $trim ? $this->trimString($this->argument($arg)) : $this->argument($arg);
    }

    /**
     * Checks if the param is set (via CLI), otherwise proposes choices to the user
     *
     * @param $param
     * @param $question
     * @param $choices
     * @param mixed $default
     *
     * @return bool|array|string|null
     */
    protected function checkParameterOrChoice($param, $question, $choices, mixed $default = null): bool|array|string|null
    {
        // Check if we already have a param set
        $value = $this->option($param);
        if ($value === null) {
            // There was no value provided via CLI, so ask the user…
            $value = $this->choice($question, $choices, $default);
        }

        return $value;
    }

    /**
     * @param      $param
     * @param      $question
     * @param bool $default
     *
     * @return string|array|bool|null
     */
    protected function checkParameterOrConfirm($param, $question, bool $default = false): string|array|bool|null
    {
        // Check if we already have a param set
        $value = $this->option($param);
        if ($value === null) {
            // There was no value provided via CLI, so ask the user...
            $value = $this->confirm($question, $default);
        }

        return $value;
    }
}
