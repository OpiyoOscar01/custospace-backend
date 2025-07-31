<?php

namespace App\Console\Commands;

use App\Models\Wiki;
use App\Services\WikiRevisionService;
use Illuminate\Console\Command;

class CleanupWikiRevisions extends Command
{
    protected $signature = 'wiki:cleanup-revisions {--keep=50 : Number of revisions to keep per wiki}';
    protected $description = 'Clean up old wiki revisions to save storage space';

    public function handle(WikiRevisionService $revisionService): int
    {
        $keepCount = (int) $this->option('keep');
        
        $this->info("Cleaning up wiki revisions, keeping {$keepCount} revisions per wiki...");

        $totalDeleted = 0;
        
        Wiki::chunk(100, function ($wikis) use ($revisionService, $keepCount, &$totalDeleted) {
            foreach ($wikis as $wiki) {
                $deleted = $revisionService->cleanupOldRevisions($wiki, $keepCount);
                $totalDeleted += $deleted;
                
                if ($deleted > 0) {
                    $this->line("Wiki #{$wiki->id}: Deleted {$deleted} old revisions");
                }
            }
        });

        $this->info("Cleanup completed. Total revisions deleted: {$totalDeleted}");

        return Command::SUCCESS;
    }
}
