<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TargetFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    }

    private function evaluator(): User
    {
        $user = User::factory()->create(['active' => true]);
        $user->assignRole('employee');

        return $user;
    }

    public function test_filtering_targets_by_position_department_and_factory(): void
    {
        $evaluator = $this->evaluator();
        $position = Position::factory()->create();
        $department = Department::factory()->create();
        $factory = Factory::factory()->create();

        $match = User::factory()->create(['active' => true, 'position_id' => $position->id]);
        $match->departments()->sync([$department->id]);
        $match->factories()->sync([$factory->id]);
        $other = User::factory()->create(['active' => true]);

        $response = $this->actingAs($evaluator)->get(route('targets.index', [
            'position_id' => $position->id,
            'department_id' => $department->id,
            'factory_id' => $factory->id,
        ]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->has('targets', 1)
            ->where('targets.0.id', $match->id));
    }

    public function test_targets_listing_is_paginated(): void
    {
        $evaluator = $this->evaluator();
        User::factory()->count(12)->create(['active' => true]);

        $response = $this->actingAs($evaluator)->get(route('targets.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('pagination.total', 12)
            ->where('pagination.last_page', 2)
            ->has('targets', 10));
    }

    public function test_q_search_filters_by_name_or_nik(): void
    {
        $evaluator = $this->evaluator();
        $match = User::factory()->create(['active' => true, 'nik' => 'JOHNDOE01']);

        $response = $this->actingAs($evaluator)->get(route('targets.index', ['q' => 'JOHNDOE01']));

        $response->assertOk()->assertInertia(fn ($page) => $page->has('targets', 1));
    }
}
