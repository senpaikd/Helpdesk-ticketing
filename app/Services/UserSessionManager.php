<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserSessionManager
{
    public function for(User $user, string $currentSessionId): Collection
    {
        return $this->query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): object => (object) [
                'id' => $session->id,
                'device' => $this->deviceLabel($session->user_agent),
                'ip_address' => $session->ip_address ?: 'Unknown IP address',
                'last_active_at' => CarbonImmutable::createFromTimestamp((int) $session->last_activity),
                'is_current' => hash_equals($currentSessionId, (string) $session->id),
            ]);
    }

    public function revokeOthers(User $user, string $currentSessionId): int
    {
        return $this->query()
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    public function deviceLabel(?string $userAgent): string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return 'Unknown device';
        }

        $browser = match (true) {
            str_contains($userAgent, 'Edg/'), str_contains($userAgent, 'Edge') => 'Edge',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Chrome') => 'Chrome',
            str_contains($userAgent, 'Safari') => 'Safari',
            default => 'Browser',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Macintosh'), str_contains($userAgent, 'macOS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown device',
        };

        return $platform === 'Unknown device' ? $platform : "{$browser} on {$platform}";
    }

    private function query(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'));
    }
}
