<?php

namespace Gowelle\SkuGenerator\Console;

use Gowelle\SkuGenerator\Models\SkuHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Artisan command to cleanup old SKU history records.
 *
 * This command allows deleting old history records based on retention policy.
 *
 * Usage:
 * ```bash
 * # Cleanup based on configured retention policy
 * php artisan sku:history:cleanup
 *
 * # Cleanup records older than specific days
 * php artisan sku:history:cleanup --days=365
 *
 * # Cleanup records before a specific date
 * php artisan sku:history:cleanup --before="2024-01-01"
 * ```
 */
class SkuHistoryCleanupCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'sku:history:cleanup
        {--days= : Delete records older than this many days}
        {--before= : Delete records before this date (YYYY-MM-DD)}
        {--force : Skip confirmation prompt}
        {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old SKU history records';

    /**
     * Execute the console command.
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        try {
            $query = SkuHistory::query();
            $cutoffDate = null;

            // Determine cutoff date
            if ($this->option('before')) {
                try {
                    $cutoffDate = Carbon::parse($this->option('before'));
                } catch (\Exception $e) {
                    $this->error("Invalid date format. Please use YYYY-MM-DD format.");
                    return Command::FAILURE;
                }
            } elseif ($this->option('days')) {
                $days = (int) $this->option('days');
                $cutoffDate = now()->subDays($days);
            } else {
                // Use configured retention policy
                $retentionDays = config('sku-generator.history.retention_days');

                if ($retentionDays === null) {
                    $this->warn('No retention policy configured and no cutoff date specified.');
                    $this->info('Use --days or --before option, or configure retention_days in config.');
                    return Command::SUCCESS;
                }

                $cutoffDate = now()->subDays($retentionDays);
            }

            // Apply date filter
            $query->beforeDate($cutoffDate);

            // Count records to be deleted
            $count = $query->count();

            if ($count === 0) {
                $this->info("No history records found before {$cutoffDate->format('Y-m-d')}");
                return Command::SUCCESS;
            }

            // Show what will be deleted
            $this->info("Found {$count} history record(s) before {$cutoffDate->format('Y-m-d')}");

            if ($this->option('dry-run')) {
                $this->comment('Dry run mode - no records will be deleted.');

                // Show sample of records
                $sample = $query->latest()->limit(10)->get();

                if ($sample->isNotEmpty()) {
                    $this->newLine();
                    $this->table(
                        ['ID', 'Event', 'SKU', 'Model', 'Date'],
                        $sample->map(function ($record) {
                            return [
                                $record->id,
                                $record->formatted_event_type,
                                $record->new_sku ?? $record->old_sku ?? '-',
                                class_basename($record->model_type),
                                $record->created_at->format('Y-m-d H:i:s'),
                            ];
                        })
                    );

                    if ($count > 10) {
                        $this->comment("Showing 10 of {$count} records...");
                    }
                }

                return Command::SUCCESS;
            }

            // Confirm deletion
            if (!$this->option('force')) {
                if (!$this->confirm("Delete {$count} history record(s) before {$cutoffDate->format('Y-m-d')}?", false)) {
                    $this->warn('Operation cancelled.');
                    return Command::SUCCESS;
                }
            }

            // Perform deletion
            $deleted = $query->delete();

            $this->info("Successfully deleted {$deleted} history record(s)");
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
