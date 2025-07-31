<?php

namespace App\Services;

use App\Models\Wiki;
use App\Models\WikiRevision;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Wiki Revision Service - Handles business logic for wiki revision operations
 */
class WikiRevisionService
{
    /**
     * Get paginated revisions for a wiki.
     */
    public function getWikiRevisions(Wiki $wiki, int $perPage = 20): LengthAwarePaginator
    {
        return $wiki->revisions()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new revision for a wiki.
     */
    public function createRevision(Wiki $wiki, array $data): WikiRevision
    {
        try {
            return DB::transaction(function () use ($wiki, $data) {
                $revision = $wiki->revisions()->create([
                    'user_id' => $data['user_id'] ?? \Auth::id(),
                    'title' => $data['title'] ?? $wiki->title,
                    'content' => $data['content'] ?? $wiki->content,
                    'summary' => $data['summary'] ?? 'Content updated',
                ]);

                Log::info('Wiki revision created', [
                    'wiki_id' => $wiki->id,
                    'revision_id' => $revision->id,
                    'user_id' => $revision->user_id
                ]);

                return $revision;
            });
        } catch (\Exception $e) {
            Log::error('Error creating wiki revision', [
                'wiki_id' => $wiki->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Restore wiki to a specific revision.
     */
    public function restoreWikiToRevision(Wiki $wiki, WikiRevision $revision): bool
    {
        try {
            return DB::transaction(function () use ($wiki, $revision) {
                // Update wiki content to match revision
                $updated = $wiki->update([
                    'title' => $revision->title,
                    'content' => $revision->content,
                ]);

                if ($updated) {
                    // Create a new revision to track the restore action
                    $this->createRevision($wiki, [
                        'title' => $revision->title,
                        'content' => $revision->content,
                        'summary' => "Restored to revision from {$revision->created_at->format('Y-m-d H:i:s')}",
                    ]);

                    Log::info('Wiki restored to revision', [
                        'wiki_id' => $wiki->id,
                        'revision_id' => $revision->id
                    ]);
                }

                return $updated;
            });
        } catch (\Exception $e) {
            Log::error('Error restoring wiki to revision', [
                'wiki_id' => $wiki->id,
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Compare two revisions and return differences.
     */
    public function compareRevisions(int $fromRevisionId, int $toRevisionId, Wiki $wiki): array
    {
        $fromRevision = WikiRevision::where('id', $fromRevisionId)
            ->where('wiki_id', $wiki->id)
            ->firstOrFail();

        $toRevision = WikiRevision::where('id', $toRevisionId)
            ->where('wiki_id', $wiki->id)
            ->firstOrFail();

        return [
            'from_revision' => [
                'id' => $fromRevision->id,
                'title' => $fromRevision->title,
                'content' => $fromRevision->content,
                'created_at' => $fromRevision->created_at,
                'user' => $fromRevision->user ? [
                    'id' => $fromRevision->user->id,
                    'name' => $fromRevision->user->name,
                ] : null,
            ],
            'to_revision' => [
                'id' => $toRevision->id,
                'title' => $toRevision->title,
                'content' => $toRevision->content,
                'created_at' => $toRevision->created_at,
                'user' => $toRevision->user ? [
                    'id' => $toRevision->user->id,
                    'name' => $toRevision->user->name,
                ] : null,
            ],
            'differences' => [
                'title_changed' => $fromRevision->title !== $toRevision->title,
                'content_changed' => $fromRevision->content !== $toRevision->content,
                'character_diff' => strlen($toRevision->content) - strlen($fromRevision->content),
                'word_diff' => str_word_count($toRevision->content) - str_word_count($fromRevision->content),
            ],
            'content_diff' => $this->generateContentDiff($fromRevision->content, $toRevision->content),
        ];
    }

    /**
     * Delete a revision (with safety checks).
     */
    public function deleteRevision(WikiRevision $revision): bool
    {
        try {
            // Don't allow deletion if it's the only revision
            $revisionCount = WikiRevision::where('wiki_id', $revision->wiki_id)->count();
            
            if ($revisionCount <= 1) {
                throw new \InvalidArgumentException('Cannot delete the last revision of a wiki');
            }

            $deleted = $revision->delete();

            if ($deleted) {
                Log::info('Wiki revision deleted', [
                    'revision_id' => $revision->id,
                    'wiki_id' => $revision->wiki_id
                ]);
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Error deleting wiki revision', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get revision statistics for a wiki.
     */
    public function getRevisionStatistics(Wiki $wiki): array
    {
        $revisions = $wiki->revisions()->with('user')->get();

        $contributors = $revisions->pluck('user')->filter()->unique('id');
        
        $recentActivity = $revisions->where('created_at', '>=', now()->subDays(30));

        return [
            'total_revisions' => $revisions->count(),
            'total_contributors' => $contributors->count(),
            'first_revision' => $revisions->sortBy('created_at')->first()?->created_at,
            'last_revision' => $revisions->sortByDesc('created_at')->first()?->created_at,
            'recent_activity' => [
                'revisions_last_30_days' => $recentActivity->count(),
                'avg_revisions_per_day' => $recentActivity->count() / 30,
            ],
            'top_contributors' => $contributors->take(5)->map(function ($user) use ($revisions) {
                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ],
                    'revision_count' => $revisions->where('user_id', $user->id)->count(),
                ];
            })->sortByDesc('revision_count')->values(),
            'content_growth' => [
                'first_content_length' => strlen($revisions->sortBy('created_at')->first()?->content ?? ''),
                'current_content_length' => strlen($wiki->content),
                'total_growth' => strlen($wiki->content) - strlen($revisions->sortBy('created_at')->first()?->content ?? ''),
            ],
        ];
    }

    /**
     * Generate basic content diff (simplified version).
     */
    private function generateContentDiff(string $oldContent, string $newContent): array
    {
        // This is a simplified diff - in production, you might want to use
        // a more sophisticated diff library like sebastianbergmann/diff
        
        $oldLines = explode("\n", $oldContent);
        $newLines = explode("\n", $newContent);

        $additions = array_diff($newLines, $oldLines);
        $deletions = array_diff($oldLines, $newLines);

        return [
            'added_lines' => array_values($additions),
            'removed_lines' => array_values($deletions),
            'added_count' => count($additions),
            'removed_count' => count($deletions),
        ];
    }

    /**
     * Check if content has significantly changed (for auto-revision creation).
     */
    public function shouldCreateRevision(string $oldContent, string $newContent, ?string $oldTitle = null, ?string $newTitle = null): bool
    {
        // Create revision if title changed
        if ($oldTitle !== $newTitle) {
            return true;
        }

        // Create revision if content change is significant
        $oldContentLength = strlen($oldContent);
        $newContentLength = strlen($newContent);
        
        // If content length changed by more than 5% or 50 characters
        $lengthDiff = abs($newContentLength - $oldContentLength);
        $percentageChange = $oldContentLength > 0 ? ($lengthDiff / $oldContentLength) * 100 : 100;

        return $percentageChange > 5 || $lengthDiff > 50;
    }

    /**
     * Clean up old revisions (keep only recent ones).
     */
    public function cleanupOldRevisions(Wiki $wiki, int $keepRevisionsCount = 50): int
    {
        try {
            $revisionsToDelete = $wiki->revisions()
                ->orderBy('created_at', 'desc')
                ->skip($keepRevisionsCount)
                ->pluck('id');

            if ($revisionsToDelete->isEmpty()) {
                return 0;
            }

            $deletedCount = WikiRevision::whereIn('id', $revisionsToDelete)->delete();

            Log::info('Cleaned up old wiki revisions', [
                'wiki_id' => $wiki->id,
                'deleted_count' => $deletedCount
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Error cleaning up old revisions', [
                'wiki_id' => $wiki->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
