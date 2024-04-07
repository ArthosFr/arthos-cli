<?php

namespace App\Commands;

use Illuminate\Support\Str;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\{text, info, warning, alert};

class RenameCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'init';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Initialize the plugin';
    protected $vendor;
    protected $vendorUri;
    protected $pluginUri;
    protected $vendorEmail;
    protected $pluginName;
    protected $pluginDesc;
    protected $namespace;
    protected $textdomain;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        info('Welcome to the plugin initialization wizard');
        alert('Beware: the case you are asked for is important!');

        $this->pluginName = text(
            label: "Enter the plugin's name",
            placeholder: "My Plugin - Capital first letters",
            required: true,
        );
        $this->pluginName = ucfirst(Str::ascii($this->pluginName));

        $this->pluginDesc = text(
            label: "Enter the plugin's description",
            placeholder: "A short description of the plugin",
            required: true,
        );
        $this->pluginDesc = htmlspecialchars($this->pluginDesc, ENT_QUOTES, 'UTF-8');

        $this->vendor = text(
            label: "Enter the author's name (or vendor)",
            placeholder: "Arthos - Capital first letter",
            required: true,
        );
        $this->vendor = ucfirst(Str::ascii($this->vendor));

        $this->vendorEmail = text(
            label: "Enter the author's email",
            placeholder: "Valid email address",
            required: true,
        );
        $this->vendorEmail = htmlspecialchars($this->vendorEmail, ENT_QUOTES, 'UTF-8');

        $this->vendorUri = text(
            label: "Enter the author's URI",
            placeholder: "https://arthos.fr - Full URL with protocol",
            required: true,
        );
        $this->vendorUri = htmlspecialchars($this->vendorUri, ENT_QUOTES, 'UTF-8');

        $this->namespace = text(
            label: "Enter the plugin's namespace",
            placeholder: "Arthos\\MyPlugin - Capital first letters and backslashes, no spaces",
            default: Str::studly(Str::ascii($this->vendor)) . '\\' . Str::studly(Str::ascii($this->pluginName)),
        );

        $this->textdomain = text(
            label: "Enter the plugin's textdomain",
            placeholder: "my-plugin - lowercase with hyphens",
            default: Str::slug(Str::ascii($this->pluginName)),
            required: true,
        );
        $this->textdomain = Str::slug(Str::ascii($this->textdomain));

        $this->info("The plugin will be initialized with the following values:");
        $this->table(
            ['Key', 'Value'],
            [
                ['Name', $this->pluginName],
                ['Description', $this->pluginDesc],
                ['Vendor', $this->vendor],
                ['Email', $this->vendorEmail],
                ['Vendor URI', $this->vendorUri],
                ['Namespace', $this->namespace],
                ['Textdomain', $this->textdomain],
            ],
        );
        $confirmed = $this->confirm('Do you want to proceed with the initialization?');

        if ($confirmed) {
            $this->initializePlugin();
        } else {
            warning('The plugin initialization has been cancelled.');
        }
    }

    public function initializePlugin(): void
    {
        $this->info('Initializing the plugin...');

        $this->renameFiles();
        $this->renamePlaceholders();
        $this->runComposer();
        $this->runNpm();
        $this->runGit();
    }

    public function renameFiles(): void
    {
        if (rename('plugin-name.php', $this->textdomain . '.php')) {
            $this->info('Renamed plugin-name.php to ' . $this->textdomain . '.php');
        } else {
            $this->error('Failed to rename plugin-name.php to ' . $this->textdomain . '.php');
        }

        if (rename('config/plugin-name.php', 'config/' . $this->textdomain . '.php')) {
            $this->info('Renamed config/plugin-name.php to config/' . $this->textdomain . '.php');
        } else {
            $this->error('Failed to rename config/plugin-name.php to config/' . $this->textdomain . '.php');
        }

        if (rename('app/Commands/PluginNameCommand.php', 'app/Commands/' . Str::studly(Str::ascii($this->pluginName)) . 'Command.php')) {
            $this->info('Renamed app/Commands/PluginNameCommand.php to app/Commands/' . Str::studly(Str::ascii($this->pluginName)) . 'Command.php');
        } else {
            $this->error('Failed to rename app/Commands/PluginNameCommand.php to app/Commands/' . Str::studly(Str::ascii($this->pluginName)) . 'Command.php');
        }
    }

    public function renamePlaceholders(): void
    {
        exec('chmod -R 777 .');

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.'));
        foreach ($files as $file) {
            if ($file->isFile()) {
                $content = file_get_contents($file->getPathname());
                $content = str_replace('__Vendor__', ucfirst($this->vendor), $content);
                $content = str_replace('__vendor__', strtolower($this->vendor), $content);
                $content = str_replace('__vendor_uri__', strtolower($this->vendorUri), $content);
                $content = str_replace('__vendor_email__', strtolower($this->vendorEmail), $content);
                $content = str_replace('__Plugin_Name__', ucfirst($this->pluginName), $content);
                $content = str_replace('__plugin_uri__', strtolower($this->pluginUri), $content);
                $content = str_replace('__Plugin_Description__', $this->pluginDesc, $content);
                $content = str_replace('__plugin_name__', strtolower(Str::studly($this->pluginName)), $content);
                $content = str_replace('__plugin-name__', strtolower($this->textdomain), $content);
                $content = str_replace('__PLUGIN_NAME__', strtoupper(Str::studly($this->pluginName)), $content);
                $content = str_replace('__PluginName__', Str::studly(ucfirst($this->pluginName)), $content);
                file_put_contents($file->getPathname(), $content);
            }
        }
    }

    public function runComposer(): void
    {
        exec('composer install && composer dump-autoload');
    }

    public function runNpm(): void
    {
        exec('npm install && npm run build');
    }

    public function runGit(): void
    {
        exec('git init && git add . && git commit -m "Initial commit"');
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
