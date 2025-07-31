<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wiki;
use App\Models\WikiRevision;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Wiki Feature Tests - Tests wiki API endpoints and functionality
 */
class WikiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        
        // Associate user with workspace
        $this->user->workspaces()->attach($this->workspace->id, ['role' => 'admin']);
        
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function test_user_can_list_wikis(): void
    {
        // Arrange
        Wiki::factory()
            ->count(5)
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create();

        // Act
        $response = $this->getJson('/api/wikis?workspace_id=' . $this->workspace->id);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'is_published',
                        'created_at',
                        'workspace',
                        'created_by'
                    ]
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function test_user_can_create_wiki(): void
    {
        // Arrange
        $wikiData = [
            'workspace_id' => $this->workspace->id,
            'title' => 'Test Wiki Article',
            'content' => 'This is the content of the test wiki article.',
            'is_published' => false,
            'metadata' => [
                'tags' => ['test', 'documentation'],
                'description' => 'A test wiki article'
            ],
            'revision_summary' => 'Initial creation'
        ];

        // Act
        $response = $this->postJson('/api/wikis', $wikiData);

        // Assert
        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'content',
                    'is_published',
                    'metadata',
                    'workspace',
                    'created_by'
                ]
            ]);

        $this->assertDatabaseHas('wikis', [
            'title' => 'Test Wiki Article',
            'slug' => 'test-wiki-article',
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id
        ]);

        // Check that revision was created
        $wiki = Wiki::where('title', 'Test Wiki Article')->first();
        $this->assertDatabaseHas('wiki_revisions', [
            'wiki_id' => $wiki->id,
            'user_id' => $this->user->id,
            'summary' => 'Initial creation'
        ]);
    }

    /** @test */
    public function test_user_can_view_wiki(): void
    {
        // Arrange
        $wiki = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create();

        // Act
        $response = $this->getJson("/api/wikis/{$wiki->id}");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'content',
                    'is_published',
                    'full_path',
                    'workspace',
                    'created_by'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $wiki->id,
                    'title' => $wiki->title
                ]
            ]);
    }

    /** @test */
    public function test_user_can_update_wiki(): void
    {
        // Arrange
        $wiki = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create();

        $updateData = [
            'title' => 'Updated Wiki Title',
            'content' => 'Updated wiki content.',
            'is_published' => true,
            'revision_summary' => 'Updated title and content'
        ];

        // Act
        $response = $this->putJson("/api/wikis/{$wiki->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated Wiki Title',
                    'is_published' => true
                ]
            ]);

        $this->assertDatabaseHas('wikis', [
            'id' => $wiki->id,
            'title' => 'Updated Wiki Title',
            'is_published' => true
        ]);

        // Check that revision was created
        $this->assertDatabaseHas('wiki_revisions', [
            'wiki_id' => $wiki->id,
            'title' => 'Updated Wiki Title',
            'summary' => 'Updated title and content'
        ]);
    }

    /** @test */
    public function test_user_can_delete_wiki(): void
    {
        // Arrange
        $wiki = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create();

        // Act
        $response = $this->deleteJson("/api/wikis/{$wiki->id}");

        // Assert
        $response->assertNoContent();
        $this->assertDatabaseMissing('wikis', ['id' => $wiki->id]);
    }

    /** @test */
    public function test_user_can_toggle_wiki_publication(): void
    {
        // Arrange
        $wiki = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->draft()
            ->create();

        // Act
        $response = $this->patchJson("/api/wikis/{$wiki->id}/toggle-publication");

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'Wiki publication status toggled successfully',
                'is_published' => true
            ]);

        $this->assertDatabaseHas('wikis', [
            'id' => $wiki->id,
            'is_published' => true
        ]);
    }

    /** @test */
    public function test_user_can_assign_user_to_wiki(): void
    {
        // Arrange
        $wiki = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create();

        $collaborator = User::factory()->create();

        // Act
        $response = $this->postJson("/api/wikis/{$wiki->id}/assign-user", [
            'user_id' => $collaborator->id,
            'role' => 'editor'
        ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'User assigned to wiki successfully'
            ]);

        $wiki->refresh();
        $this->assertNotNull($wiki->metadata['collaborators']);
        $this->assertEquals($collaborator->id, $wiki->metadata['collaborators'][0]['user_id']);
        $this->assertEquals('editor', $wiki->metadata['collaborators'][0]['role']);
    }

    /** @test */
    public function test_user_can_get_wiki_tree(): void
    {
        // Arrange
        $parentWiki = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create();

        $childWiki = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->withParent($parentWiki)
            ->create();

        // Act
        $response = $this->getJson("/api/wikis/tree?workspace_id={$this->workspace->id}");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'children'
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_user_can_search_wikis(): void
    {
        // Arrange
        Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create(['title' => 'Laravel Documentation']);

        Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create(['title' => 'PHP Best Practices']);

        // Act
        $response = $this->getJson("/api/wikis/search?workspace_id={$this->workspace->id}&q=Laravel");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug'
                    ]
                ]
            ])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_user_can_get_wiki_breadcrumb(): void
    {
        // Arrange
        $grandparent = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create(['title' => 'Grandparent']);

        $parent = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->withParent($grandparent)
            ->create(['title' => 'Parent']);

        $child = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->withParent($parent)
            ->create(['title' => 'Child']);

        // Act
        $response = $this->getJson("/api/wikis/{$child->id}/breadcrumb");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug'
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_user_can_find_wiki_by_slug(): void
    {
        // Arrange
        $wiki = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create(['slug' => 'unique-test-slug']);

        // Act
        $response = $this->getJson("/api/wikis/find-by-slug?workspace_id={$this->workspace->id}&slug=unique-test-slug");

        // Assert
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $wiki->id,
                    'slug' => 'unique-test-slug'
                ]
            ]);
    }

    /** @test */
    public function test_user_can_duplicate_wiki(): void
    {
        // Arrange
        $originalWiki = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create(['title' => 'Original Wiki']);

        // Act
        $response = $this->postJson("/api/wikis/{$originalWiki->id}/duplicate", [
            'title' => 'Duplicated Wiki'
        ]);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'title' => 'Duplicated Wiki',
                    'is_published' => false
                ]
            ]);

        $this->assertDatabaseHas('wikis', [
            'title' => 'Duplicated Wiki',
            'workspace_id' => $this->workspace->id,
            'created_by_id' => $this->user->id
        ]);
    }

    /** @test */
    public function test_unauthorized_user_cannot_access_wikis(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        $wiki = Wiki::factory()
            ->forWorkspace($otherWorkspace)
            ->createdBy($otherUser)
            ->create();

        // Act
        $response = $this->getJson("/api/wikis/{$wiki->id}");

        // Assert
        $response->assertForbidden();
    }

    /** @test */
    public function test_validation_fails_for_invalid_wiki_data(): void
    {
        // Act
        $response = $this->postJson('/api/wikis', [
            'title' => '', // Empty title should fail
            'content' => '', // Empty content should fail
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content', 'workspace_id']);
    }

    /** @test */
    public function test_slug_uniqueness_within_workspace(): void
    {
        // Arrange
        Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create(['slug' => 'existing-slug']);

        // Act
        $response = $this->postJson('/api/wikis', [
            'workspace_id' => $this->workspace->id,
            'title' => 'New Wiki',
            'slug' => 'existing-slug',
            'content' => 'Content here'
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    /** @test */
    public function test_circular_reference_prevention_in_parent_child_relationship(): void
    {
        // Arrange
        $parentWiki = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->create();

        $childWiki = Wiki::factory()
            ->forWorkspace($this->workspace)
            ->createdBy($this->user)
            ->withParent($parentWiki)
            ->create();

        // Act - Try to make parent wiki a child of its own child
        $response = $this->putJson("/api/wikis/{$parentWiki->id}", [
            'parent_id' => $childWiki->id
        ]);

        // Assert
        $response->assertUnprocessable();
    }
    /** @test */
public function test_user_can_view_wiki_revisions(): void
{
    // Arrange
    $wiki = Wiki::factory()
        ->forWorkspace($this->workspace)
        ->createdBy($this->user)
        ->create();

    WikiRevision::factory()
        ->count(3)
        ->forWiki($wiki)
        ->byUser($this->user)
        ->create();

    // Act
    $response = $this->getJson("/api/wikis/{$wiki->id}/revisions");

    // Assert
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'content_preview',
                    'summary',
                    'created_at',
                    'user'
                ]
            ]
        ])
        ->assertJsonCount(4, 'data'); // 3 created + 1 initial from wiki creation
        }

        /** @test */
        public function test_user_can_restore_wiki_to_previous_revision(): void
        {
            // Arrange
            $wiki = Wiki::factory()
                ->forWorkspace($this->workspace)
                ->createdBy($this->user)
                ->create(['title' => 'Original Title', 'content' => 'Original content']);

            // Update wiki to create a revision
            $wiki->update(['title' => 'Updated Title', 'content' => 'Updated content']);

            $originalRevision = $wiki->revisions()->oldest()->first();

            // Act
            $response = $this->postJson("/api/wikis/{$wiki->id}/revisions/{$originalRevision->id}/restore");

            // Assert
            $response->assertOk();
            
            $wiki->refresh();
            $this->assertEquals('Original Title', $wiki->title);
            $this->assertEquals('Original content', $wiki->content);
        }

        /** @test */
        public function test_user_can_compare_wiki_revisions(): void
        {
            // Arrange
            $wiki = Wiki::factory()
                ->forWorkspace($this->workspace)
                ->createdBy($this->user)
                ->create();

            $revision1 = WikiRevision::factory()
                ->forWiki($wiki)
                ->create(['content' => 'First version']);

            $revision2 = WikiRevision::factory()
                ->forWiki($wiki)
                ->create(['content' => 'Second version with more content']);

            // Act
            $response = $this->getJson("/api/wikis/{$wiki->id}/compare-revisions?from_revision_id={$revision1->id}&to_revision_id={$revision2->id}");

            // Assert
            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'from_revision',
                        'to_revision',
                        'differences',
                        'content_diff'
                    ]
                ]);
        }

        /** @test */
        public function test_user_can_get_revision_statistics(): void
        {
            // Arrange
            $wiki = Wiki::factory()
                ->forWorkspace($this->workspace)
                ->createdBy($this->user)
                ->create();

            WikiRevision::factory()
                ->count(5)
                ->forWiki($wiki)
                ->create();

            // Act
            $response = $this->getJson("/api/wikis/{$wiki->id}/revision-statistics");

            // Assert
            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'total_revisions',
                        'total_contributors',
                        'recent_activity',
                        'top_contributors',
                        'content_growth'
                    ]
                ]);
        }

}
