<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_and_create_one_anonymous_comment_per_target_and_period(): void
    {
        $user = $this->userWithRole('employee');
        $target = User::factory()->create(['active' => true]);
        $period = EvaluationPeriod::create(['month' => 10, 'year' => 2026, 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('comments.index', $target))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Comments/Index')->where('target.id', $target->id)->where('userComment', false));

        $this->actingAs($user)
            ->post(route('comments.store', $target), ['body' => 'Komentar pertama'])
            ->assertRedirect(route('comments.index', $target));

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'target_id' => $target->id,
            'evaluation_period_id' => $period->id,
            'body' => 'Komentar pertama',
        ]);

        $this->actingAs($user)
            ->post(route('comments.store', $target), ['body' => 'Komentar kedua'])
            ->assertRedirect(route('comments.index', $target))
            ->assertSessionHasErrors('body');
    }

    public function test_user_can_delete_own_comment_and_create_replacement(): void
    {
        $user = $this->userWithRole('employee');
        $target = User::factory()->create(['active' => true]);
        $period = EvaluationPeriod::create(['month' => 10, 'year' => 2026, 'status' => 'active']);
        $comment = Comment::create([
            'user_id' => $user->id,
            'target_id' => $target->id,
            'evaluation_period_id' => $period->id,
            'body' => 'Salah ketik',
        ]);

        $this->actingAs($user)->delete(route('comments.destroy', $comment))->assertRedirect(route('comments.index', $target));
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);

        $this->actingAs($user)
            ->post(route('comments.store', $target), ['body' => 'Komentar pengganti'])
            ->assertRedirect(route('comments.index', $target));
        $this->assertDatabaseHas('comments', ['body' => 'Komentar pengganti']);
    }

    public function test_admin_can_delete_another_users_comment_and_comments_are_paginated(): void
    {
        $admin = $this->userWithRole('admin');
        $target = User::factory()->create(['active' => true]);
        $period = EvaluationPeriod::create(['month' => 10, 'year' => 2026, 'status' => 'active']);

        $comments = User::factory(11)->create()->each(function (User $user) use ($target, $period): void {
            Comment::create([
                'user_id' => $user->id,
                'target_id' => $target->id,
                'evaluation_period_id' => $period->id,
                'body' => 'Komentar anonim',
            ]);
        });

        $this->actingAs($admin)
            ->get(route('comments.index', $target))
            ->assertInertia(fn (Assert $page) => $page->where('comments.per_page', 10)->where('comments.total', 11));

        $this->actingAs($admin)->delete(route('comments.destroy', $comments->first()))->assertRedirect(route('comments.index', $target));
        $this->assertDatabaseMissing('comments', ['id' => $comments->first()->id]);
    }

    private function userWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create(['active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
