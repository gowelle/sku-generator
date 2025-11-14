<?php

namespace Gowelle\SkuGenerator\Console;

use Gowelle\SkuGenerator\Models\SkuHistory;
use Illuminate\Console\Command;

/**
 * Artisan command to view SKU history.
 *
 * This command allows viewing SKU history records with various filtering options.
 *
 * Usage:
 * ```bash
 * # View history for a specific model
 * php artisan sku:history "App\Models\Product" --id=123
 *
 * # View history for a specific SKU
 * php artisan sku:history --sku="TM-TSH-ABC12345"
 *
 * # View recent changes
 * php artisan sku:history --recent --days=7
 * ```
 */
class SkuHistoryCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'sku:history
        {model? : The fully qualified model class name}
        {--id= : The model ID}
        {--sku= : The SKU to search for}
        {--recent : Show recent changes}
        {--days=7 : Number of days for recent changes}
        {--event= : Filter by event type (created, regenerated, modified, deleted)}
        {--limit=50 : Maximum number of records to display}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View SKU history and audit trail';

    /**
     * Execute the console command.
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        try {
            $query = SkuHistory::query();

            // Filter by model and ID
            if ($this->argument('model') && $this->option('id')) {
                $modelClass = $this->argument('model');

                if (!class_exists($modelClass)) {
                    $this->error("Model class {$modelClass} does not exist.");
                    return Command::FAILURE;
                }

                $query->where('model_type', $modelClass)
                    ->where('model_id', $this->option('id'));

                $this->info("Showing history for {$modelClass} ID: {$this->option('id')}");
            }

            // Filter by SKU
            if ($this->option('sku')) {
                $query->forSku($this->option('sku'));
                $this->info("Showing history for SKU: {$this->option('sku')}");
            }

            // Filter by recent changes
            if ($this->option('recent')) {
                $days = (int) $this->option('days');
                $query->recentChanges($days);
                $this->info("Showing changes from the last {$days} days");
            }

            // Filter by event type
            if ($this->option('event')) {
                $eventType = $this->option('event');
                $validEvents = ['created', 'regenerated', 'modified', 'deleted'];

                if (!in_array($eventType, $validEvents)) {
                    $this->error("Invalid event type. Must be one of: " . implode(', ', $validEvents));
                    return Command::FAILURE;
                }

                $query->byEventType($eventType);
                $this->info("Filtering by event type: {$eventType}");
            }

            // Apply limit and order
            $limit = (int) $this->option('limit');
            $history = $query->latest()
                ->limit($limit)
                ->get();

            if ($history->isEmpty()) {
                $this->warn('No history records found.');
                return Command::SUCCESS;
            }

            // Display results
            $this->newLine();
            $this->table(
                ['ID', 'Event', 'Old SKU', 'New SKU', 'Model', 'Model ID', 'User ID', 'Date'],
                $history->map(function ($record) {
                    return [
                        $record->id,
                        $record->formatted_event_type,
                        $record->old_sku ?? '-',
                        $record->new_sku ?? '-',
                        class_basename($record->model_type),
                        $record->model_id,
                        $record->user_id ?? '-',
                        $record->created_at->format('Y-m-d H:i:s'),
                    ];
                })
            );

            $total = $history->count();
            $this->newLine();
            $this->info("Showing {$total} record(s)");

            if ($total >= $limit) {
                $this->comment("Note: Results limited to {$limit} records. Use --limit to see more.");
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
