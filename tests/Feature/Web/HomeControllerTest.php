<?php

declare(strict_types=1);

use App\Domain\Category\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Home Page', function () {
    it('renders featured products and categories for the current tenant', function () {
        $tenant = Tenant::factory()->create(['is_active' => true]);
        Context::add('tenant_id', $tenant->id);

        $category = Category::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Electronics']);

        Product::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->get('/en');

        $response->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Home')
                ->has('featuredProducts', 3)
                ->has('categories', 1)
                ->where('categories.0.name', 'Electronics')
            );
    });

    it('excludes inactive products from featured products', function () {
        $tenant = Tenant::factory()->create(['is_active' => true]);
        Context::add('tenant_id', $tenant->id);

        Product::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);
        Product::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);

        $response = $this->get('/en');

        $response->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Home')
                ->has('featuredProducts', 1)
            );
    });
});
