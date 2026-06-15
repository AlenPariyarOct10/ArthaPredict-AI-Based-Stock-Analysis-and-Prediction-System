<?php

namespace Tests\Feature;

use App\Models\ArthaNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArthaNoteLazyLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_artha_notes_expose_the_next_batch_without_pagination_controls(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 11) as $index) {
            ArthaNote::create([
                'user_id' => $user->id,
                'type' => 'insight',
                'title' => "ArthaNote {$index}",
                'content' => '<p>Market insight</p>',
                'hashtags' => ['nepse'],
            ]);
        }

        $firstPage = $this->actingAs($user)->get(route('arthanotes.index'));

        $firstPage
            ->assertOk()
            ->assertViewHas('notes', fn ($notes) => $notes->count() === 10)
            ->assertSee('id="arthaNotesLoader"', false)
            ->assertSee('page=2', false)
            ->assertDontSee('aria-label="Pagination Navigation"', false);

        $secondPage = $this->actingAs($user)->get(route('arthanotes.index', ['page' => 2]));

        $secondPage
            ->assertOk()
            ->assertViewHas('notes', fn ($notes) => $notes->count() === 1)
            ->assertSee('data-next-url=""', false)
            ->assertDontSee('aria-label="Pagination Navigation"', false);
    }
}
