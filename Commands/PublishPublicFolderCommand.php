<?php

namespace Apiato\Core\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishPublicFolderCommand extends Command
{
    protected $signature = 'mp:publish:public';

    protected $description = 'Command description';

    private  $path = 'MagicPort/Ship/Public' ;

    public function handle(): void
    {
        $filePath = app_path($this->path);

        $pathFolder = File::exists($filePath);

       if (!$pathFolder) {
            $this->error('Folder not found');
            return;
        }

       foreach (glob($filePath . '/*') as $file) {
           if (File::isDirectory($file))
           {
               File::copyDirectory($file, public_path() . '/' . basename($file));
           }
           else{
                File::copy($file, public_path() . '/' . basename($file));
           }
           
        

       }
       
        $this->info('Folders successfully published');
       
    }
}
