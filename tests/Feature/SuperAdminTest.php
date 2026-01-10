<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load migrations from Main App (FlowKosmo)
        $this->artisan('migrate', [
            '--path' => '/var/www/flowkosmo/database/migrations',
            '--realpath' => true,
        ]);
        
        // Load migrations from Admin App
        $this->artisan('migrate', [
            '--path' => 'database/migrations',
        ]);
    }

    public function test_dashboard_stats_loads()
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'name' => 'Super Admin',
            'email' => 'super@admin.com',
        ]);

        // Create dummy data
        Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'domain' => 'tenant-a.flowkosmo.com',
            'plan' => 'pro',
        ]);
        
        Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'domain' => 'tenant-b.flowkosmo.com',
            'plan' => 'free',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'total_tenants',
                'active_tenants',
                'plans' => ['free', 'pro', 'whitelabel'],
                'system' => ['cpu', 'ram', 'disk'],
                'charts'
            ]);
            
        $this->assertEquals(2, $response->json('total_tenants'));
    }

    public function test_tenants_list_loads()
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        Tenant::create(['name' => 'T1', 'slug' => 't1']);
        Tenant::create(['name' => 'T2', 'slug' => 't2']);

        $response = $this->actingAs($admin)
            ->getJson('/api/tenants');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_suspend_tenant()
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::create(['name' => 'Bad Tenant', 'slug' => 'bad', 'is_suspended' => false]);

        $response = $this->actingAs($admin)
            ->postJson("/api/tenants/{$tenant->id}/suspend");
            
        $response->assertOk();
        
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'is_suspended' => true
        ]);
    }
    
    public function test_invites_flow()
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        // Create Invite
        $response = $this->actingAs($admin)
            ->postJson('/api/invites', [
                'email' => 'new@tenant.com',
                // 'plan' => 'pro' // Not implemented
            ]);
            
        $response->assertCreated();
        
        $this->assertDatabaseHas('invitations', [
            'email' => 'new@tenant.com',
            'created_by' => $admin->id
        ]);
        
        // List Invites
        $response = $this->actingAs($admin)
             ->getJson('/api/invites');
             
        $response->assertOk();
    }
}
