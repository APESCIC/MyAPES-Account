<?php

namespace App\Console\Commands;

use App\Core\Extensions\Modules\ModulePackageRegistry;
use App\Core\Extensions\Plugins\PluginRegistry;
use Illuminate\Console\Command;

class PluginsListCommand extends Command
{
    protected $signature = 'myapes:plugins-list';

    protected $description = 'List registered plugin package manifests';

    public function handle(PluginRegistry $plugins, ModulePackageRegistry $modules): int
    {
        $this->info('Modules:');
        foreach ($modules->modules() as $module) {
            $enabled = $modules->isEnabled($module->slug) ? 'enabled' : 'disabled';
            $this->line("  - {$module->slug} ({$module->name}) [{$enabled}] {$module->routePrefix}");
        }

        $this->newLine();
        $this->info('Plugins:');
        foreach ($plugins->plugins() as $plugin) {
            $this->line("  - {$plugin->slug} v{$plugin->version} requiresCore={$plugin->requiresCore}");
            $this->line("      translationNamespace={$plugin->translationNamespace}");
            $this->line("      searchKeywordsKey={$plugin->searchKeywordsKey}");
            $this->line('      compatible='.implode(',', $plugin->compatibleModules));
            $this->line('      shipped='.implode(',', $plugin->shippedModules));
            $deps = array_map(
                static fn ($dependency): string => $dependency->pluginSlug,
                $plugin->dependencies,
            );
            $this->line('      dependencies='.($deps === [] ? '(none)' : implode(',', $deps)));
        }

        return self::SUCCESS;
    }
}
