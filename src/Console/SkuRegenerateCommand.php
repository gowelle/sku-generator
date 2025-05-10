<?php

namespace Gowelle\SkuGenerator\Console;

use Illuminate\Console\Command;

class SkuRegenerateCommand extends Command
{
    protected $signature = 'sku:regenerate {model}';

    protected $description = 'Regenerate SKUs for the given model';

    public function handle()
    {
        $modelClass = $this->argument('model');

        if (! class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");
            return 1;
        }

        if (! in_array('Gowelle\SkuGenerator\Concerns\HasSku', class_uses_recursive($modelClass))) {
            $this->error("Model class {$modelClass} must use the HasSku trait.");
            return 1;
        }

        $count = 0;

        $modelClass::chunk(100, function ($models) use (&$count) {
            foreach ($models as $model) {
                $oldSku = $model->sku;
                $model->forceRegenerateSku();
                $this->line("Updated SKU: {$oldSku} → {$model->sku}");
                $count++;
            }
        });

        $this->info("✅ Finished regenerating SKUs for {$count} records.");
    }
}
