<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\MuPluginInstaller;
use Brain\Monkey;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the MuPluginInstaller class.
 */
class MuPluginInstallerTest extends TestCase {

	/**
	 * Set up Brain Monkey.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		if ( ! \defined( 'WPMU_PLUGIN_DIR' ) ) {
			\define( 'WPMU_PLUGIN_DIR', '/tmp/wp-content/mu-plugins' );
		}
		if ( ! \defined( 'WP_PLUGIN_DIR' ) ) {
			\define( 'WP_PLUGIN_DIR', '/tmp/wp-content/plugins' );
		}
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
	 * Verify loader_path returns the correct path.
	 *
	 * @return void
	 */
	public function test_loader_path(): void {
		$this->assertSame(
			\WPMU_PLUGIN_DIR . '/agency-pass-loader.php',
			MuPluginInstaller::loader_path(),
		);
	}

	/**
	 * Verify loader_content generates valid PHP.
	 *
	 * @return void
	 */
	public function test_loader_content_is_valid_php(): void {
		$content = MuPluginInstaller::loader_content();

		$this->assertStringStartsWith( '<?php', $content );
		$this->assertStringContainsString( 'agency-pass/plugin.php', $content );
		$this->assertStringContainsString( 'file_exists', $content );
		$this->assertStringContainsString( 'require_once', $content );
	}
}
