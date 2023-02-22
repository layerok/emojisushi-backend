<?php namespace System\Classes\UpdateManager;

use App;
use Event;

/**
 * ManagesApp
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
trait ManagesApp
{
    /**
     * migrateApp runs migrations on the app directory
     */
    public function migrateApp()
    {
        // Suppress the "Nothing to migrate" message
        if (isset($this->notesOutput)) {
            $this->migrator->setOutput(new \Symfony\Component\Console\Output\NullOutput);

            Event::listen(\Illuminate\Database\Events\MigrationsStarted::class, function() {
                $this->migrator->setOutput($this->notesOutput);
            });
        }

        if ($this->migrator->run(app_path('database/migrations'))) {
            $this->migrateCount++;
        }
    }

    /**
     * seedModule runs seeds  on the app directory
     */
    public function seedApp()
    {
        $className = 'App\Database\Seeds\DatabaseSeeder';
        if (!class_exists($className)) {
            return;
        }

        $this->note('<info>Seeding App</info>');

        $seeder = App::make($className);

        if ($cmd = $this->getNotesCommand()) {
            $seeder->setCommand($cmd);
        }

        $seeder->run();
    }
}
