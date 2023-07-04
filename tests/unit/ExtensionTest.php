<?php declare(strict_types=1);

namespace MerkushinTest\SenseiReportsExtension;

use Merkushin\SenseiReportsExtension\Extension;
use Merkushin\Wpal\ServiceFactory;
use Merkushin\Wpal\Service\Hooks;
use PHPUnit\Framework\TestCase; 

class ExtensionTest extends TestCase {
	/**
	 * @dataProvider providerInit_WhenCorrectGetParamGiven_AddsActions
	 */
	public function testInit_WhenCorrectGetParamGiven_AddsActions($get_params): void {
		// Arrange
		$_GET = $get_params;
		$hooks = $this->createMock(Hooks::class);
		ServiceFactory::set_custom_hooks($hooks);

		$extension = new Extension();

		// Expect
		$hooks
			->expects( $this->exactly( 2 ) )
			->method( 'add_action' )
			->withConsecutive(
				[ 'admin_enqueue_scripts', [ $extension, 'enqueue_scripts' ] ],
				[ 'sensei_analysis_after_headers', [ $extension, 'add_graph_canvas' ] ]
			);

		// Act
		$extension->init();
	}

	public function providerInit_WhenCorrectGetParamGiven_AddsActions(): array {
		return [
			'page and view' => [
				[
					'page' => 'sensei_reports',
					'view' => 'students',
				],
			],
			'no view' => [
				[
					'page' => 'sensei_reports',
				],
			],
		];
	}


	/**
	 * @dataProvider providerInit_WithoutStudentReportGetParams_AddsActions
	 */
	public function testInit_WithoutStudentReportGetParams_AddsActions( array $get_params ): void {
		// Arrange
		$_GET = $get_params;
		$hooks = $this->createMock(Hooks::class);
		ServiceFactory::set_custom_hooks($hooks);

		$extension = new Extension();

		// Expect
		$hooks
			->expects( $this->never() )
			->method( 'add_action' );

		// Act
		$extension->init();
	}

	public function providerInit_WithoutStudentReportGetParams_AddsActions(): array {
		return [
			'empty' => [
				[],
			],
			'wrong view' => [
				[
					'page' => 'sensei_reports',
					'view' => 'wrong',
				],
			],
			'wrong page' => [
				[
					'page' => 'wrong',
					'view' => 'students',
				],
			],
		];
	}
}
