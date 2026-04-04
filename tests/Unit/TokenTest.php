<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\Token;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Token class.
 */
class TokenTest extends TestCase {

	/**
	 * Set up Brain Monkey.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain Monkey.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Verify generate creates a token and stores it as a transient.
	 *
	 * @return void
	 */
	public function test_generate_stores_transient(): void {
		Functions\expect( 'set_transient' )
			->once()
			->withArgs(
				static function ( string $key, array $value, int $ttl ): bool {
					return \str_starts_with( $key, 'agency_pass_token_' )
					&& $value['email'] === 'test@example.tld'
					&& $value['ip'] === '127.0.0.1'
					&& isset( $value['created_at'] )
					&& $ttl === 900;
				},
			);

		$token = Token::generate( 'test@example.tld', '127.0.0.1' );

		$this->assertSame( 64, \strlen( $token ) );
	}

	/**
	 * Verify validate returns data and deletes the transient.
	 *
	 * @return void
	 */
	public function test_validate_returns_data_and_deletes(): void {
		$expected = [
			'email'      => 'test@example.tld',
			'created_at' => \time(),
			'ip'         => '127.0.0.1',
		];

		Functions\expect( 'get_transient' )
			->once()
			->andReturn( $expected );

		Functions\expect( 'delete_transient' )
			->once();

		$result = Token::validate( 'abc123' );

		$this->assertSame( $expected, $result );
	}

	/**
	 * Verify validate returns null for missing/expired tokens.
	 *
	 * @return void
	 */
	public function test_validate_returns_null_for_invalid_token(): void {
		Functions\expect( 'get_transient' )
			->once()
			->andReturn( false );

		Functions\expect( 'delete_transient' )->never();

		$this->assertNull( Token::validate( 'nonexistent' ) );
	}

	/**
	 * Verify default TTL is 900 seconds.
	 *
	 * @return void
	 */
	public function test_default_ttl(): void {
		$this->assertSame( 900, Token::ttl() );
	}
}
