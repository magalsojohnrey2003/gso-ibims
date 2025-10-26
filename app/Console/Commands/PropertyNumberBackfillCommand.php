<?php

namespace App\Console\Commands;

use App\Models\ItemInstance;
use App\Services\PropertyNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PropertyNumberBackfillCommand extends Command
{
    protected $signature = 'propertynumber:backfill
        {--dry-run : Preview changes without persisting}
        {--force : Overwrite existing component columns}
        {--chunk=200 : How many records to process per chunk}';

    protected $description = 'Populate property number components on item instances based on existing canonical strings.';

    public function handle(PropertyNumberService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $chunk = (int) $this->option('chunk') ?: 200;

        $this->info('Scanning item instances for property numbers...');

        $updated = 0;
        $skipped = 0;
        $failed = [];
        $processed = 0;

        $total = ItemInstance::count();
        if ($total > 0) {
            $this->info("Total instances to scan: {$total}");
            $this->output->progressStart($total);
        }

        ItemInstance::query()
            ->with('item')
            ->orderBy('id')
            ->chunkById($chunk, function ($instances) use ($service, $dryRun, $force, &$updated, &$skipped, &$failed, &$processed) {
                foreach ($instances as $instance) {
                    $processed++;
                    $candidate = $this->findCandidate($instance);
                    if (! $candidate) {
                        $skipped++;
                        $this->output->progressAdvance();
                        continue;
                    }

                    try {
                        $components = $service->parse($candidate);
                    } catch (InvalidArgumentException $exception) {
                        $failed[] = [
                            'id' => $instance->id,
                            'value' => $candidate,
                            'reason' => $exception->getMessage(),
                        ];
                        $this->output->progressAdvance();
                        continue;
                    }

                    $updates = $this->buildUpdates($instance, $components, $force);
                    if (empty($updates)) {
                        $skipped++;
                        $this->output->progressAdvance();
                        continue;
                    }

                    if ($dryRun) {
                        $updated++;
                        $this->line(sprintf('[DRY-RUN] #%d -> %s', $instance->id, json_encode($updates)));
                        $this->output->progressAdvance();
                        continue;
                    }

                    $updates['updated_at'] = now();
                    DB::table('item_instances')
                        ->where('id', $instance->id)
                        ->update($updates);

                    $updated++;
                    $this->output->progressAdvance();
                }
            });

        if ($total > 0) {
            $this->output->progressFinish();
        }

        $this->newLine();
        $this->info(sprintf('Processed %d instance(s); updated %d; skipped %d.', $processed, $updated, $skipped));

        if (! empty($failed)) {
            $this->warn('Could not parse the following rows:');
            foreach (array_slice($failed, 0, 10) as $row) {
                $this->warn(sprintf(' - #%d [%s]: %s', $row['id'], $row['value'], $row['reason']));
            }
            if (count($failed) > 10) {
                $this->warn(sprintf('... and %d more.', count($failed) - 10));
            }
        }

        return self::SUCCESS;
    }

    private function findCandidate(ItemInstance $instance): ?string
    {
        $fields = [
            $instance->property_number,
            $instance->serial,
            $instance->notes,
            optional($instance->item)->name,
        ];

        foreach ($fields as $field) {
            if (! is_string($field) || trim($field) === '') {
                continue;
            }
            if ($this->looksLikePropertyNumber($field)) {
                return $this->firstPropertyNumber($field);
            }
        }

        return null;
    }

    private function looksLikePropertyNumber(string $value): bool
    {
        // tolerate variable serial segment (allow alnum serial) and optional GLA digits
        return (bool) preg_match('/\b\d{4}-[A-Z0-9]{1,4}-\d{1,4}-[A-Za-z0-9\-]{1,}-[A-Za-z0-9]{1,4}\b/i', $value);
    }

    private function firstPropertyNumber(string $value): ?string
    {
        if (preg_match('/(\d{4}-[A-Z0-9]{1,4}-\d{1,4}-[A-Za-z0-9\-]{1,}-[A-Za-z0-9]{1,4})/i', $value, $matches)) {
            return strtoupper($matches[1]);
        }
        return null;
    }

    private function buildUpdates(ItemInstance $instance, array $components, bool $force): array
    {
        $mapping = [
            'property_number' => $components['property_number'] ?? null,
            'year_procured' => isset($components['year']) ? (int) $components['year'] : null,
            // store category into category_code
            'category_code' => $components['category'] ?? null,
            'gla' => $components['gla'] ?? null,
            'serial' => $components['serial'] ?? null,
            'serial_int' => $components['serial_int'] ?? null,
            'office_code' => $components['office'] ?? null,
        ];

        $updates = [];
        foreach ($mapping as $column => $value) {
            if ($value === null) {
                continue;
            }

            $current = $instance->{$column};
            $isNull = $current === null;
            if ($force || $isNull) {
                $updates[$column] = $value;
            }
        }

        return $updates;
    }

}