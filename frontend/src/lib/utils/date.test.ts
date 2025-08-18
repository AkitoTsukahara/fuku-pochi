import { describe, it, expect, beforeEach, vi } from 'vitest';
import { formatDate, formatRelativeTime } from './date';

describe('date utilities', () => {
	beforeEach(() => {
		// Reset all mocks before each test
		vi.clearAllMocks();
	});

	describe('formatDate', () => {
		it('should format date string to Japanese locale', () => {
			const dateString = '2023-01-15T14:30:00Z';
			const result = formatDate(dateString);
			
			// The exact format may vary by environment, but it should contain key elements
			expect(result).toMatch(/2023/);
			expect(result).toMatch(/1/);
			expect(result).toMatch(/15/);
		});

		it('should handle different date formats', () => {
			const testCases = [
				'2023-12-25T09:00:00Z',
				'2023-06-01T18:45:30.123Z',
				'2023-03-10T00:00:00+09:00'
			];

			testCases.forEach(dateString => {
				const result = formatDate(dateString);
				expect(result).toBeTruthy();
				expect(typeof result).toBe('string');
			});
		});

		it('should include time information', () => {
			const dateString = '2023-01-15T14:30:00Z';
			const result = formatDate(dateString);
			
			// Should contain time information (hour and minute)
			expect(result).toMatch(/\d{2}:\d{2}/);
		});

		it('should handle edge case dates', () => {
			const testCases = [
				'2000-01-01T00:00:00Z', // Start of millennium
				'2023-02-29T12:00:00Z', // Non-leap year (should handle gracefully)
				'2023-12-31T23:59:59Z'  // End of year
			];

			testCases.forEach(dateString => {
				expect(() => formatDate(dateString)).not.toThrow();
			});
		});
	});

	describe('formatRelativeTime', () => {
		let mockNow: Date;

		beforeEach(() => {
			// Mock current time to 2023-01-15T15:00:00Z
			mockNow = new Date('2023-01-15T15:00:00Z');
			vi.setSystemTime(mockNow);
		});

		it('should return "たった今" for recent timestamps (< 1 minute)', () => {
			const recentTime = new Date(mockNow.getTime() - 30 * 1000); // 30 seconds ago
			const result = formatRelativeTime(recentTime.toISOString());
			expect(result).toBe('たった今');
		});

		it('should return minutes for timestamps within an hour', () => {
			const fiveMinutesAgo = new Date(mockNow.getTime() - 5 * 60 * 1000);
			const result = formatRelativeTime(fiveMinutesAgo.toISOString());
			expect(result).toBe('5分前');

			const thirtyMinutesAgo = new Date(mockNow.getTime() - 30 * 60 * 1000);
			const result2 = formatRelativeTime(thirtyMinutesAgo.toISOString());
			expect(result2).toBe('30分前');

			const fiftyNineMinutesAgo = new Date(mockNow.getTime() - 59 * 60 * 1000);
			const result3 = formatRelativeTime(fiftyNineMinutesAgo.toISOString());
			expect(result3).toBe('59分前');
		});

		it('should return hours for timestamps within a day', () => {
			const twoHoursAgo = new Date(mockNow.getTime() - 2 * 60 * 60 * 1000);
			const result = formatRelativeTime(twoHoursAgo.toISOString());
			expect(result).toBe('2時間前');

			const twelveHoursAgo = new Date(mockNow.getTime() - 12 * 60 * 60 * 1000);
			const result2 = formatRelativeTime(twelveHoursAgo.toISOString());
			expect(result2).toBe('12時間前');

			const twentyThreeHoursAgo = new Date(mockNow.getTime() - 23 * 60 * 60 * 1000);
			const result3 = formatRelativeTime(twentyThreeHoursAgo.toISOString());
			expect(result3).toBe('23時間前');
		});

		it('should return days for timestamps within a week', () => {
			const oneDayAgo = new Date(mockNow.getTime() - 1 * 24 * 60 * 60 * 1000);
			const result = formatRelativeTime(oneDayAgo.toISOString());
			expect(result).toBe('1日前');

			const threeDaysAgo = new Date(mockNow.getTime() - 3 * 24 * 60 * 60 * 1000);
			const result2 = formatRelativeTime(threeDaysAgo.toISOString());
			expect(result2).toBe('3日前');

			const sixDaysAgo = new Date(mockNow.getTime() - 6 * 24 * 60 * 60 * 1000);
			const result3 = formatRelativeTime(sixDaysAgo.toISOString());
			expect(result3).toBe('6日前');
		});

		it('should return formatted date for timestamps older than a week', () => {
			const eightDaysAgo = new Date(mockNow.getTime() - 8 * 24 * 60 * 60 * 1000);
			const result = formatRelativeTime(eightDaysAgo.toISOString());
			
			// Should fall back to formatDate
			expect(result).toMatch(/2023/);
			expect(result).toMatch(/1/);
			expect(result).toMatch(/[7-8]/); // 8 days ago from Jan 15 is Jan 7, but formatting may show Jan 8
		});

		it('should handle boundary cases correctly', () => {
			// Exactly 1 minute
			const oneMinuteAgo = new Date(mockNow.getTime() - 60 * 1000);
			const result1 = formatRelativeTime(oneMinuteAgo.toISOString());
			expect(result1).toBe('1分前');

			// Exactly 1 hour
			const oneHourAgo = new Date(mockNow.getTime() - 60 * 60 * 1000);
			const result2 = formatRelativeTime(oneHourAgo.toISOString());
			expect(result2).toBe('1時間前');

			// Exactly 1 day
			const oneDayAgo = new Date(mockNow.getTime() - 24 * 60 * 60 * 1000);
			const result3 = formatRelativeTime(oneDayAgo.toISOString());
			expect(result3).toBe('1日前');

			// Exactly 7 days
			const sevenDaysAgo = new Date(mockNow.getTime() - 7 * 24 * 60 * 60 * 1000);
			const result4 = formatRelativeTime(sevenDaysAgo.toISOString());
			// Should fall back to formatDate since it's not < 7 days
			expect(result4).toMatch(/2023/);
		});

		it('should handle future timestamps gracefully', () => {
			const futureTime = new Date(mockNow.getTime() + 60 * 1000); // 1 minute in future
			const result = formatRelativeTime(futureTime.toISOString());
			// Should still work without throwing errors
			expect(result).toBeTruthy();
		});
	});
});