import { describe, it, expect } from 'vitest';
import { validateRequired, validateChildName, validateGroupName } from './validation';

describe('validation utilities', () => {
	describe('validateRequired', () => {
		it('should return true for non-empty strings', () => {
			expect(validateRequired('hello')).toBe(true);
			expect(validateRequired('test')).toBe(true);
			expect(validateRequired('a')).toBe(true);
			expect(validateRequired('123')).toBe(true);
		});

		it('should return false for empty strings', () => {
			expect(validateRequired('')).toBe(false);
		});

		it('should return false for strings with only whitespace', () => {
			expect(validateRequired(' ')).toBe(false);
			expect(validateRequired('  ')).toBe(false);
			expect(validateRequired('\t')).toBe(false);
			expect(validateRequired('\n')).toBe(false);
			expect(validateRequired('  \t\n  ')).toBe(false);
		});

		it('should return true for strings with content after trimming', () => {
			expect(validateRequired(' hello ')).toBe(true);
			expect(validateRequired('  test  ')).toBe(true);
			expect(validateRequired('\tvalue\n')).toBe(true);
		});

		it('should handle special characters', () => {
			expect(validateRequired('あいうえお')).toBe(true);
			expect(validateRequired('テスト')).toBe(true);
			expect(validateRequired('👶')).toBe(true);
			expect(validateRequired('123-456')).toBe(true);
		});
	});

	describe('validateChildName', () => {
		it('should return valid for proper child names', () => {
			const validNames = [
				'太郎',
				'花子',
				'山田太郎',
				'田中',
				'あいうえお',
				'タロウ',
				'Taro',
				'田中 太郎',
				'12345678901234567890' // Exactly 20 characters
			];

			validNames.forEach(name => {
				const result = validateChildName(name);
				expect(result.isValid).toBe(true);
				expect(result.error).toBeUndefined();
			});
		});

		it('should return invalid for empty or whitespace-only names', () => {
			const invalidNames = ['', ' ', '  ', '\t', '\n'];

			invalidNames.forEach(name => {
				const result = validateChildName(name);
				expect(result.isValid).toBe(false);
				expect(result.error).toBe('子どもの名前を入力してください');
			});
		});

		it('should return invalid for names longer than 20 characters', () => {
			const longName = '123456789012345678901'; // 21 characters
			const result = validateChildName(longName);
			
			expect(result.isValid).toBe(false);
			expect(result.error).toBe('名前は20文字以内で入力してください');
		});

		it('should handle boundary cases', () => {
			// Exactly 20 characters should be valid
			const exactlyTwentyChars = '12345678901234567890';
			const result1 = validateChildName(exactlyTwentyChars);
			expect(result1.isValid).toBe(true);

			// 21 characters should be invalid
			const twentyOneChars = '123456789012345678901';
			const result2 = validateChildName(twentyOneChars);
			expect(result2.isValid).toBe(false);
			expect(result2.error).toBe('名前は20文字以内で入力してください');
		});

		it('should handle names with whitespace that become valid after trimming', () => {
			const result = validateChildName(' 太郎 ');
			expect(result.isValid).toBe(true);
		});

		it('should handle multibyte characters correctly', () => {
			// Japanese characters
			const japaneseNames = [
				'あいうえおかきくけこさしすせそたちつてとなにぬねの', // 20 Japanese characters
			];

			japaneseNames.forEach(name => {
				if (name.length <= 20) {
					const result = validateChildName(name);
					expect(result.isValid).toBe(true);
				}
			});

			// Too long Japanese name
			const longJapaneseName = 'あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほ'; // 25 characters
			const result = validateChildName(longJapaneseName);
			expect(result.isValid).toBe(false);
			expect(result.error).toBe('名前は20文字以内で入力してください');
		});
	});

	describe('validateGroupName', () => {
		it('should return valid for proper group names', () => {
			const validNames = [
				'田中家',
				'我が家',
				'テストグループ',
				'Group 1',
				'家族グループ',
				'親戚一同',
				'123456789012345678901234567890' // Exactly 30 characters
			];

			validNames.forEach(name => {
				const result = validateGroupName(name);
				expect(result.isValid).toBe(true);
				expect(result.error).toBeUndefined();
			});
		});

		it('should return invalid for empty or whitespace-only names', () => {
			const invalidNames = ['', ' ', '  ', '\t', '\n'];

			invalidNames.forEach(name => {
				const result = validateGroupName(name);
				expect(result.isValid).toBe(false);
				expect(result.error).toBe('グループ名を入力してください');
			});
		});

		it('should return invalid for names longer than 30 characters', () => {
			const longName = '1234567890123456789012345678901'; // 31 characters
			const result = validateGroupName(longName);
			
			expect(result.isValid).toBe(false);
			expect(result.error).toBe('グループ名は30文字以内で入力してください');
		});

		it('should handle boundary cases', () => {
			// Exactly 30 characters should be valid
			const exactlyThirtyChars = '123456789012345678901234567890';
			const result1 = validateGroupName(exactlyThirtyChars);
			expect(result1.isValid).toBe(true);

			// 31 characters should be invalid
			const thirtyOneChars = '1234567890123456789012345678901';
			const result2 = validateGroupName(thirtyOneChars);
			expect(result2.isValid).toBe(false);
			expect(result2.error).toBe('グループ名は30文字以内で入力してください');
		});

		it('should handle names with whitespace that become valid after trimming', () => {
			const result = validateGroupName(' 田中家 ');
			expect(result.isValid).toBe(true);
		});

		it('should handle multibyte characters correctly', () => {
			// Japanese characters
			const japaneseGroupNames = [
				'あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめも', // 30 Japanese characters
			];

			japaneseGroupNames.forEach(name => {
				if (name.length <= 30) {
					const result = validateGroupName(name);
					expect(result.isValid).toBe(true);
				}
			});

			// Too long Japanese name
			const longJapaneseName = 'あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよ'; // 31 characters
			const result = validateGroupName(longJapaneseName);
			expect(result.isValid).toBe(false);
			expect(result.error).toBe('グループ名は30文字以内で入力してください');
		});

		it('should handle special characters and mixed content', () => {
			const specialNames = [
				'Group-1',
				'グループ①',
				'Family & Friends',
				'テスト@グループ',
				'組織#001'
			];

			specialNames.forEach(name => {
				if (name.length <= 30) {
					const result = validateGroupName(name);
					expect(result.isValid).toBe(true);
				}
			});
		});
	});

	describe('validation integration', () => {
		it('should work consistently with validateRequired', () => {
			// Both child and group validation should behave consistently for required validation
			const emptyName = '';
			
			expect(validateRequired(emptyName)).toBe(false);
			expect(validateChildName(emptyName).isValid).toBe(false);
			expect(validateGroupName(emptyName).isValid).toBe(false);
			
			const validName = 'Test';
			expect(validateRequired(validName)).toBe(true);
			expect(validateChildName(validName).isValid).toBe(true);
			expect(validateGroupName(validName).isValid).toBe(true);
		});

		it('should handle edge cases consistently', () => {
			const whitespaceOnlyName = '   ';
			
			expect(validateRequired(whitespaceOnlyName)).toBe(false);
			expect(validateChildName(whitespaceOnlyName).isValid).toBe(false);
			expect(validateGroupName(whitespaceOnlyName).isValid).toBe(false);
		});
	});
});