<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

class ServiceCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new service class';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $className = $this->argument('name');
        $serviceFolderPath = app_path('Services');
        $fileName = "$className.php";
        $fullPath = "$serviceFolderPath/$fileName";
        $fileExists = (file_exists($fullPath) && is_writable($fullPath));
        if ($fileExists) {
            $this->error('Service class with the name exists');
            exit;
        }
        try {
            $fileHandle = fopen($fullPath, 'w+');
            $created = fwrite($fileHandle, $this->contents($className)); //@phpstan-ignore-line
            if (is_int($created)) {
                $this->info("$className created successfully @ $fullPath");
                exit;
            }
            $this->error('Unable to create service class');
        } catch (\Throwable $th) {
            logger($th);
            $this->error('Unable to create service class');
        }
    }

    /**
     * @return array<string,mixed>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => ['The name of the service class your are creating?', 'E.g AmadeusService'],
        ];
    }

    private function contents(string $className): string
    {
        $contents = <<<EOT
        <?php

        namespace App\Services;

        class $className extends BaseService {

        }
        EOT;

        return $contents;
    }
}
