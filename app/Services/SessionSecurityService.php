<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionSecurityService
{
    public const USER_ID_KEY =
        'smartfind_session_user_id';

    public const AUTHENTICATED_AT_KEY =
        'smartfind_authenticated_at';

    public const LAST_ACTIVITY_KEY =
        'smartfind_last_activity_at';

    public const PASSWORD_FINGERPRINT_KEY =
        'smartfind_password_fingerprint';

    /**
     * Bind the current Laravel session to the authenticated user and the
     * current password hash. Only a one-way SHA-256 fingerprint is stored.
     */
    public function initialize(
        Request $request,
        User $user,
        ?int $authenticatedAt = null
    ): void {
        $now = time();

        $request->session()->put([
            self::USER_ID_KEY =>
                (int) $user->getAuthIdentifier(),

            self::AUTHENTICATED_AT_KEY =>
                $authenticatedAt ?? $now,

            self::LAST_ACTIVITY_KEY =>
                $now,

            self::PASSWORD_FINGERPRINT_KEY =>
                $this->passwordFingerprint($user),
        ]);
    }

    /**
     * SHA-256 of the password hash is enough to detect a password rotation
     * without storing another reusable credential in session data.
     */
    public function passwordFingerprint(User $user): string
    {
        return hash(
            'sha256',
            (string) $user->getAuthPassword()
        );
    }

    /**
     * Rotate the current authenticated session after a password change,
     * update the fingerprint, and revoke all other database sessions.
     */
    public function rotateAfterPasswordChange(
        Request $request,
        User $user
    ): void {
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        $this->initialize(
            $request,
            $user
        );

        $this->revokeDatabaseSessionsForUser(
            $user,
            $request->session()->getId()
        );
    }

    /**
     * Remove every persisted database session owned by a user, optionally
     * preserving one session ID.
     */
    public function revokeDatabaseSessionsForUser(
        User $user,
        ?string $exceptSessionId = null
    ): int {
        $context = $this->databaseSessionContext();

        if ($context === null) {
            return 0;
        }

        [$connection, $table] = $context;

        $query = $connection
            ->table($table)
            ->where(
                'user_id',
                $user->getAuthIdentifier()
            );

        if (
            is_string($exceptSessionId)
            && $exceptSessionId !== ''
        ) {
            $query->where(
                'id',
                '!=',
                $exceptSessionId
            );
        }

        return $query->delete();
    }

    /**
     * Keep at most N sessions per user when database sessions are enabled.
     * The current session may not be persisted until the response ends, so
     * only max-1 previously persisted sessions are retained.
     */
    public function pruneConcurrentSessions(
        User $user,
        string $currentSessionId
    ): int {
        $maximum = max(
            0,
            (int) config(
                'smartfind_security.session.max_concurrent_sessions',
                3
            )
        );

        if ($maximum === 0) {
            return 0;
        }

        $context = $this->databaseSessionContext();

        if ($context === null) {
            return 0;
        }

        [$connection, $table] = $context;

        $oldSessionIds = $connection
            ->table($table)
            ->where(
                'user_id',
                $user->getAuthIdentifier()
            )
            ->where(
                'id',
                '!=',
                $currentSessionId
            )
            ->orderByDesc('last_activity')
            ->pluck('id')
            ->values();

        $allowedOldSessions = max(
            0,
            $maximum - 1
        );

        $deleteIds = $oldSessionIds
            ->slice($allowedOldSessions)
            ->filter(
                fn ($id) =>
                    is_string($id)
                    && $id !== ''
            )
            ->values();

        if ($deleteIds->isEmpty()) {
            return 0;
        }

        return $connection
            ->table($table)
            ->whereIn(
                'id',
                $deleteIds->all()
            )
            ->delete();
    }

    /**
     * Resolve the Laravel database-session table without assuming the default
     * DB connection. Non-database session drivers safely return null.
     *
     * @return array{0: Connection, 1: string}|null
     */
    private function databaseSessionContext(): ?array
    {
        if (
            strtolower(
                (string) config('session.driver')
            ) !== 'database'
        ) {
            return null;
        }

        $table = trim(
            (string) config(
                'session.table',
                'sessions'
            )
        );

        if ($table === '') {
            return null;
        }

        $connectionName = config(
            'session.connection'
        );

        $connection = DB::connection(
            is_string($connectionName)
            && $connectionName !== ''
                ? $connectionName
                : null
        );

        if (! $connection
            ->getSchemaBuilder()
            ->hasTable($table)) {
            return null;
        }

        return [
            $connection,
            $table,
        ];
    }
}
