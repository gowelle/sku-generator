<?php

namespace Gowelle\SkuGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Prompts;

/**
 * Artisan command to regenerate SKUs for models.
 *
 * This command allows regenerating SKUs for models that use the HasSku trait.
 * It supports both interactive mode and direct model class specification.
 *
 * Usage:
 * ```bash
 * # Interactive mode
 * php artisan sku:regenerate
 *
 * # Direct model specification
 * php artisan sku:regenerate "App\Models\Product"
 * ```
 */
class SkuRegenerateCommand extends Command
{
    /**
     * The console command signature.
     *
     * The model argument is optional. If not provided, the command will
     * prompt the user to select from available models.
     *
     * @var string
     */
    protected $signature = 'sku:regenerate 
        {model? : The fully qualified model class name}
        {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate SKUs for models using the HasSku trait';

    /**
     * Execute the console command.
     *
     * Process:
     * 1. Resolve model class (from argument or selection)
     * 2. Validate model exists and uses HasSku trait
     * 3. Confirm regeneration with user
     * 4. Process models in chunks to avoid memory issues
     * 5. Show progress and results
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        try {
            $modelClass = $this->argument('model') ?? Prompts\select(
                'Select model to regenerate SKUs for',
                $this->getAvailableModels()
            );

            if (!class_exists($modelClass)) {
                $this->error("Model class {$modelClass} does not exist.");
                return Command::FAILURE;
            }

            if (!in_array('Gowelle\SkuGenerator\Concerns\HasSku', class_uses_recursive($modelClass))) {
                $this->error("Model class {$modelClass} must use the HasSku trait.");
                return Command::FAILURE;
            }

            $total = $modelClass::count();
            
            if ($total === 0) {
                $this->warn("No records found for {$modelClass}.");
                return Command::SUCCESS;
            }

            if (!$this->option('force') && 
                !$this->confirm("Are you sure you want to regenerate {$total} SKUs for {$modelClass}?", false)
            ) {
                $this->warn('Operation cancelled.');
                return Command::SUCCESS;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $count = 0;
            $failed = [];

            $modelClass::chunk(100, function ($models) use (&$count, &$failed, $bar) {
                foreach ($models as $model) {
                    try {
                        $oldSku = $model->sku;
                        $model->forceRegenerateSku();
                        
                        $this->line(" Updated: {$oldSku} → {$model->sku}");
                        $count++;
                    } catch (\Exception $e) {
                        $failed[] = [
                            'id' => $model->getKey(),
                            'sku' => $model->sku,
                            'error' => $e->getMessage()
                        ];
                    }
                    
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine(2);

            if (!empty($failed)) {
                $this->error(sprintf(
                    '%d/%d SKUs failed to regenerate. See logs for details.',
                    count($failed),
                    $total
                ));
                Log::error('SKU regeneration failures:', $failed);
                return Command::FAILURE;
            }

            $this->info("✅ Successfully regenerated {$count} SKUs");
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Get available models from configuration.
     *
     * Returns an array of model class names that are configured
     * for SKU generation in the sku-generator config file.
     *
     * @return array<string> Array of fully qualified model class names
     */
    protected function getAvailableModels(): array
    {
        return collect(config('sku-generator.models'))
            ->keys()
            ->filter(fn(string $model): bool => class_exists($model))
            ->mapWithKeys(fn(string $model): array => [
                $model => class_basename($model)
            ])
            ->toArray();
    }
}
