<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Services\AI\AiProviderManager;
use App\Services\AI\Providers\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAssistantFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_role_can_load_admin_assistant_session(): void
    {
        $user = $this->makeUser('manager', 'manager-assistant@example.com');

        $response = $this
            ->actingAs($user)
            ->getJson(route('admin.assistant.session'));

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonCount(1, 'messages');

        $this->assertDatabaseHas('conversations', [
            'channel' => 'admin_panel_assistant',
            'visitor_id' => 'admin-user-' . $user->id,
        ]);
    }

    public function test_internal_role_can_send_message_and_store_reply(): void
    {
        $user = $this->makeUser('admin', 'admin-assistant@example.com');

        app()->instance(AiProviderManager::class, new class extends AiProviderManager {
            public function provider(): AiProvider
            {
                return new class implements AiProvider {
                    public function chat(array $messages): array
                    {
                        return [
                            'content' => 'Check the Clients section first, then open the client profile to review invoices and project history.',
                            'model' => 'fake-admin-assistant',
                            'tokens_in' => 21,
                            'tokens_out' => 17,
                            'duration_ms' => 12,
                        ];
                    }

                    public function name(): string
                    {
                        return 'fake';
                    }
                };
            }
        });

        $sessionResponse = $this
            ->actingAs($user)
            ->getJson(route('admin.assistant.session'))
            ->assertOk();

        $conversationId = (string) $sessionResponse->json('conversation_id');

        $response = $this
            ->actingAs($user)
            ->postJson(route('admin.assistant.message'), [
                'conversation_id' => $conversationId,
                'message' => 'How do I check a client invoice quickly?',
                'page_title' => 'Clients',
                'page_heading' => 'Client Management',
                'current_path' => '/admin/clients',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('conversation_id', $conversationId)
            ->assertJsonPath('message.role', 'assistant');

        $conversation = Conversation::query()->findOrFail($conversationId);

        $this->assertSame('admin_panel_assistant', $conversation->channel);
        $this->assertSame(3, $conversation->messages()->count());
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => 'How do I check a client invoice quickly?',
        ]);
    }

    public function test_non_internal_user_cannot_access_admin_assistant_routes(): void
    {
        $clientUser = $this->makeUser('client', 'client-assistant@example.com');

        $this
            ->actingAs($clientUser)
            ->getJson(route('admin.assistant.session'))
            ->assertForbidden();

        $this
            ->actingAs($clientUser)
            ->postJson(route('admin.assistant.message'), [
                'message' => 'hello',
            ])
            ->assertForbidden();
    }

    private function makeUser(string $role, string $email): User
    {
        return User::query()->create([
            'name' => ucfirst($role) . ' User',
            'email' => $email,
            'password' => bcrypt('secret123'),
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
