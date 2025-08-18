import { describe, it, expect, beforeEach, vi } from 'vitest';
import { categoriesApi } from './categories';
import type { ClothingCategory } from '$lib/data/types';

// Mock the apiClient
vi.mock('./client', () => ({
	apiClient: {
		get: vi.fn()
	}
}));

import { apiClient } from './client';
const mockApiClient = vi.mocked(apiClient);

describe('categoriesApi', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	const mockCategories: ClothingCategory[] = [
		{
			id: 1,
			name: 'Tシャツ',
			icon_path: '/icons/tshirt.svg',
			sort_order: 1
		},
		{
			id: 2,
			name: 'ズボン',
			icon_path: '/icons/pants.svg',
			sort_order: 2
		},
		{
			id: 3,
			name: '靴下',
			icon_path: '/icons/socks.svg',
			sort_order: 3
		},
		{
			id: 4,
			name: 'ハンカチ',
			icon_path: '/icons/handkerchief.svg',
			sort_order: 4
		},
		{
			id: 5,
			name: '肌着',
			icon_path: '/icons/underwear.svg',
			sort_order: 5
		}
	];

	describe('getClothingCategories', () => {
		it('should get all clothing categories successfully', async () => {
			mockApiClient.get.mockResolvedValueOnce(mockCategories);

			const result = await categoriesApi.getClothingCategories();

			expect(mockApiClient.get).toHaveBeenCalledWith('/clothing-categories', undefined);
			expect(result).toEqual(mockCategories);
		});

		it('should pass fetch function to apiClient', async () => {
			const mockFetch = vi.fn();

			mockApiClient.get.mockResolvedValueOnce(mockCategories);

			const result = await categoriesApi.getClothingCategories(mockFetch);

			expect(mockApiClient.get).toHaveBeenCalledWith('/clothing-categories', mockFetch);
			expect(result).toEqual(mockCategories);
		});

		it('should return empty array when no categories exist', async () => {
			const emptyCategories: ClothingCategory[] = [];

			mockApiClient.get.mockResolvedValueOnce(emptyCategories);

			const result = await categoriesApi.getClothingCategories();

			expect(mockApiClient.get).toHaveBeenCalledWith('/clothing-categories', undefined);
			expect(result).toEqual([]);
		});

		it('should handle API errors', async () => {
			mockApiClient.get.mockRejectedValueOnce(new Error('Failed to fetch categories'));

			await expect(categoriesApi.getClothingCategories())
				.rejects
				.toThrow('Failed to fetch categories');

			expect(mockApiClient.get).toHaveBeenCalledWith('/clothing-categories', undefined);
		});

		it('should handle categories with optional fields', async () => {
			const categoriesWithOptionalFields: ClothingCategory[] = [
				{
					id: 1,
					name: 'カテゴリ1'
					// icon_path and sort_order are optional
				},
				{
					id: 2,
					name: 'カテゴリ2',
					icon_path: '/icons/category2.svg'
					// sort_order is optional
				},
				{
					id: 3,
					name: 'カテゴリ3',
					sort_order: 3
					// icon_path is optional
				}
			];

			mockApiClient.get.mockResolvedValueOnce(categoriesWithOptionalFields);

			const result = await categoriesApi.getClothingCategories();

			expect(mockApiClient.get).toHaveBeenCalledWith('/clothing-categories', undefined);
			expect(result).toEqual(categoriesWithOptionalFields);
			
			// Check that optional fields are handled correctly
			expect(result[0].icon_path).toBeUndefined();
			expect(result[0].sort_order).toBeUndefined();
			expect(result[1].sort_order).toBeUndefined();
			expect(result[2].icon_path).toBeUndefined();
		});
	});

	describe('getCategories (alias)', () => {
		it('should call the same endpoint as getClothingCategories', async () => {
			mockApiClient.get.mockResolvedValueOnce(mockCategories);

			const result = await categoriesApi.getCategories();

			expect(mockApiClient.get).toHaveBeenCalledWith('/clothing-categories', undefined);
			expect(result).toEqual(mockCategories);
		});

		it('should pass fetch function to apiClient like getClothingCategories', async () => {
			const mockFetch = vi.fn();

			mockApiClient.get.mockResolvedValueOnce(mockCategories);

			const result = await categoriesApi.getCategories(mockFetch);

			expect(mockApiClient.get).toHaveBeenCalledWith('/clothing-categories', mockFetch);
			expect(result).toEqual(mockCategories);
		});
	});

	describe('categories data validation', () => {
		it('should handle categories with all possible data types', async () => {
			const categoriesWithAllTypes: ClothingCategory[] = [
				{
					id: 1,
					name: 'テスト1',
					icon_path: '/icons/test1.svg',
					sort_order: 1
				},
				{
					id: 2,
					name: 'テスト2',
					icon_path: null as any, // Testing null icon_path
					sort_order: 0 // Testing zero sort_order
				}
			];

			mockApiClient.get.mockResolvedValueOnce(categoriesWithAllTypes);

			const result = await categoriesApi.getClothingCategories();

			expect(result).toHaveLength(2);
			expect(result[0]).toMatchObject({
				id: 1,
				name: 'テスト1',
				icon_path: '/icons/test1.svg',
				sort_order: 1
			});
			expect(result[1]).toMatchObject({
				id: 2,
				name: 'テスト2',
				sort_order: 0
			});
		});
	});
});