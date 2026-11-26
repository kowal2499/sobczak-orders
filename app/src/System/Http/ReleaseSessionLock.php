<?php

namespace App\System\Http;

/**
 * Closes the session before the action runs, so parallel requests of the same user are not serialised
 * on the session file lock. Only for read-only actions that never write to the session.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class ReleaseSessionLock
{
}
