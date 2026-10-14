<?php declare( strict_types=1 );

namespace Merkushin\ReportsGraphForSensei\Tests;

use DateTimeZone;
use Merkushin\ReportsGraphForSensei\ReportRequest;
use PHPUnit\Framework\TestCase;

class ReportRequestTest extends TestCase {
	/**
	 * @dataProvider providerStudentsReport
	 *
	 * @param array<string, mixed> $query Query arguments.
	 */
	public function testIsStudentsReport_StudentsReport_ReturnsTrue( array $query ): void {
		self::assertTrue( ( new ReportRequest( $query ) )->is_students_report() );
	}

	/**
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function providerStudentsReport(): array {
		return [
			'default view'  => [ [ 'page' => 'sensei_reports' ] ],
			'students view' => [
				[
					'page' => 'sensei_reports',
					'view' => 'students',
				],
			],
			'with filters'  => [
				[
					'page'       => 'sensei_reports',
					'view'       => 'students',
					'start_date' => '2026-10-01',
					's'          => 'search',
				],
			],
			'empty user ID' => [
				[
					'page'    => 'sensei_reports',
					'user_id' => '',
				],
			],
		];
	}

	/**
	 * @dataProvider providerOtherPages
	 *
	 * @param array<string, mixed> $query Query arguments.
	 */
	public function testIsStudentsReport_OtherPage_ReturnsFalse( array $query ): void {
		self::assertFalse( ( new ReportRequest( $query ) )->is_students_report() );
	}

	/**
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function providerOtherPages(): array {
		return [
			'no page'          => [ [] ],
			'other page'       => [
				[
					'page' => 'sensei_grading',
					'view' => 'students',
				],
			],
			'courses report'   => [
				[
					'page' => 'sensei_reports',
					'view' => 'courses',
				],
			],
			'lessons report'   => [
				[
					'page' => 'sensei_reports',
					'view' => 'lessons',
				],
			],
			'one student'      => [
				[
					'page'    => 'sensei_reports',
					'user_id' => '5',
				],
			],
			'one course'       => [
				[
					'page'      => 'sensei_reports',
					'course_id' => '7',
				],
			],
			'one lesson'       => [
				[
					'page'      => 'sensei_reports',
					'lesson_id' => '9',
				],
			],
			'page as an array' => [ [ 'page' => [ 'sensei_reports' ] ] ],
		];
	}

	public function testGetDates_GivenDates_ReturnsThem(): void {
		$request = new ReportRequest(
			[
				'start_date' => '2026-10-01',
				'end_date'   => '2026-10-14',
			]
		);

		self::assertSame( '2026-10-01', $request->get_start_date() );
		self::assertSame( '2026-10-14', $request->get_end_date() );
	}

	public function testGetDates_NoDates_ReturnsEmptyStrings(): void {
		$request = new ReportRequest( [ 'end_date' => [ '2026-10-14' ] ] );

		self::assertSame( '', $request->get_start_date() );
		self::assertSame( '', $request->get_end_date() );
	}

	public function testGetTimezone_ValidTimezone_ReturnsIt(): void {
		$request = new ReportRequest( [ 'timezone' => 'Asia/Kathmandu' ] );

		self::assertSame( 'Asia/Kathmandu', $request->get_timezone( new DateTimeZone( 'UTC' ) )->getName() );
	}

	/**
	 * @dataProvider providerInvalidTimezones
	 *
	 * @param mixed $timezone Timezone argument.
	 */
	public function testGetTimezone_MissingOrInvalid_ReturnsFallback( $timezone ): void {
		$request = new ReportRequest( [ 'timezone' => $timezone ] );

		self::assertSame( 'Europe/Berlin', $request->get_timezone( new DateTimeZone( 'Europe/Berlin' ) )->getName() );
	}

	/**
	 * @return array<string, array{mixed}>
	 */
	public function providerInvalidTimezones(): array {
		return [
			'empty'    => [ '' ],
			'unknown'  => [ 'Mars/Olympus_Mons' ],
			'array'    => [ [ 'UTC' ] ],
			'injected' => [ "UTC'; DROP TABLE wp_users; --" ],
		];
	}
}
