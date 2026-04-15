<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Interface for audit trail logger implementations.
 *
 * Implementations bridge Agency Pass events to external audit trail plugins.
 * Each implementation must check its backing plugin's availability at runtime.
 */
interface AuditLoggerInterface {

	/**
	 * Checks whether the backing audit trail plugin is installed and active.
	 *
	 * @return bool
	 */
	public static function is_available(): bool;

	/**
	 * Registers hooks to capture Agency Pass events.
	 *
	 * @return void
	 */
	public static function register_hooks(): void;
}
