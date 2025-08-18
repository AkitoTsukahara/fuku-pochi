import { describe, it, expect, beforeEach, vi } from 'vitest';
import { stockApi } from './stock';
import type { StockResponse, StockItem, IncrementStockRequest, DecrementStockRequest } from '$lib/data/types';

// Mock the apiClient
vi.mock('./client', () => ({
	apiClient: {
		get: vi.fn(),
		post: vi.fn()
	}
}));

import { apiClient } from './client';
const mockApiClient = vi.mocked(apiClient);

describe('stockApi', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	const mockStockResponse: StockResponse = {
		child_id: '1',
		child_name: 'Test Child',
		stock_items: [
			{
				stock_item_id: '1',
				clothing_category_id: 1,
				clothing_category: {
					id: 1,
					name: 'Tシャツ',
					icon_path: '/icons/tshirt.svg',
					sort_order: 1
				},
				current_count: 3
			},
			{
				stock_item_id: '2',
				clothing_category_id: 2,
				clothing_category: {
					id: 2,
					name: 'ズボン',
					icon_path: '/icons/pants.svg',
					sort_order: 2
				},
				current_count: 2
			}
		]
	};

	const mockStockItem: StockItem = {
		stock_item_id: '1',
		clothing_category_id: 1,
		clothing_category: {
			id: 1,
			name: 'Tシャツ',
			icon_path: '/icons/tshirt.svg',
			sort_order: 1
		},
		current_count: 4
	};

	describe('getChildStock', () => {
		it('should get child stock successfully', async () => {
			const childId = '1';

			mockApiClient.get.mockResolvedValueOnce(mockStockResponse);

			const result = await stockApi.getChildStock(childId);

			expect(mockApiClient.get).toHaveBeenCalledWith(`/children/${childId}/stock`, undefined);
			expect(result).toEqual(mockStockResponse);
		});

		it('should pass fetch function to apiClient', async () => {
			const childId = '1';
			const mockFetch = vi.fn();

			mockApiClient.get.mockResolvedValueOnce(mockStockResponse);

			const result = await stockApi.getChildStock(childId, mockFetch);

			expect(mockApiClient.get).toHaveBeenCalledWith(`/children/${childId}/stock`, mockFetch);
			expect(result).toEqual(mockStockResponse);
		});

		it('should handle child not found', async () => {
			const childId = 'nonexistent';

			mockApiClient.get.mockRejectedValueOnce(new Error('Child not found'));

			await expect(stockApi.getChildStock(childId))
				.rejects
				.toThrow('Child not found');

			expect(mockApiClient.get).toHaveBeenCalledWith(`/children/${childId}/stock`, undefined);
		});
	});

	describe('getStock (alias)', () => {
		it('should call the same endpoint as getChildStock', async () => {
			const childId = '1';

			mockApiClient.get.mockResolvedValueOnce(mockStockResponse);

			const result = await stockApi.getStock(childId);

			expect(mockApiClient.get).toHaveBeenCalledWith(`/children/${childId}/stock`, undefined);
			expect(result).toEqual(mockStockResponse);
		});
	});

	describe('incrementStock', () => {
		it('should increment stock successfully', async () => {
			const childId = '1';
			const request: IncrementStockRequest = {
				clothing_category_id: 1,
				increment: 1
			};

			mockApiClient.post.mockResolvedValueOnce(mockStockItem);

			const result = await stockApi.incrementStock(childId, request);

			expect(mockApiClient.post).toHaveBeenCalledWith(`/children/${childId}/stock-increment`, request, undefined);
			expect(result).toEqual(mockStockItem);
		});

		it('should pass fetch function to apiClient', async () => {
			const childId = '1';
			const request: IncrementStockRequest = {
				clothing_category_id: 1,
				increment: 2
			};
			const mockFetch = vi.fn();

			mockApiClient.post.mockResolvedValueOnce(mockStockItem);

			const result = await stockApi.incrementStock(childId, request, mockFetch);

			expect(mockApiClient.post).toHaveBeenCalledWith(`/children/${childId}/stock-increment`, request, mockFetch);
			expect(result).toEqual(mockStockItem);
		});

		it('should handle validation errors', async () => {
			const childId = '1';
			const request: IncrementStockRequest = {
				clothing_category_id: 999, // Invalid category
				increment: 1
			};

			mockApiClient.post.mockRejectedValueOnce(new Error('Invalid clothing category'));

			await expect(stockApi.incrementStock(childId, request))
				.rejects
				.toThrow('Invalid clothing category');

			expect(mockApiClient.post).toHaveBeenCalledWith(`/children/${childId}/stock-increment`, request, undefined);
		});

		it('should handle increment value validation', async () => {
			const childId = '1';
			const request: IncrementStockRequest = {
				clothing_category_id: 1,
				increment: -1 // Invalid increment
			};

			mockApiClient.post.mockRejectedValueOnce(new Error('Increment must be positive'));

			await expect(stockApi.incrementStock(childId, request))
				.rejects
				.toThrow('Increment must be positive');

			expect(mockApiClient.post).toHaveBeenCalledWith(`/children/${childId}/stock-increment`, request, undefined);
		});
	});

	describe('decrementStock', () => {
		it('should decrement stock successfully', async () => {
			const childId = '1';
			const request: DecrementStockRequest = {
				clothing_category_id: 1,
				decrement: 1
			};
			const decrementedStockItem: StockItem = {
				...mockStockItem,
				current_count: 2
			};

			mockApiClient.post.mockResolvedValueOnce(decrementedStockItem);

			const result = await stockApi.decrementStock(childId, request);

			expect(mockApiClient.post).toHaveBeenCalledWith(`/children/${childId}/stock-decrement`, request, undefined);
			expect(result).toEqual(decrementedStockItem);
		});

		it('should pass fetch function to apiClient', async () => {
			const childId = '1';
			const request: DecrementStockRequest = {
				clothing_category_id: 1,
				decrement: 1
			};
			const mockFetch = vi.fn();

			mockApiClient.post.mockResolvedValueOnce(mockStockItem);

			const result = await stockApi.decrementStock(childId, request, mockFetch);

			expect(mockApiClient.post).toHaveBeenCalledWith(`/children/${childId}/stock-decrement`, request, mockFetch);
			expect(result).toEqual(mockStockItem);
		});

		it('should handle insufficient stock', async () => {
			const childId = '1';
			const request: DecrementStockRequest = {
				clothing_category_id: 1,
				decrement: 5 // More than available
			};

			mockApiClient.post.mockRejectedValueOnce(new Error('Insufficient stock'));

			await expect(stockApi.decrementStock(childId, request))
				.rejects
				.toThrow('Insufficient stock');

			expect(mockApiClient.post).toHaveBeenCalledWith(`/children/${childId}/stock-decrement`, request, undefined);
		});

		it('should handle validation errors', async () => {
			const childId = '1';
			const request: DecrementStockRequest = {
				clothing_category_id: 999, // Invalid category
				decrement: 1
			};

			mockApiClient.post.mockRejectedValueOnce(new Error('Invalid clothing category'));

			await expect(stockApi.decrementStock(childId, request))
				.rejects
				.toThrow('Invalid clothing category');

			expect(mockApiClient.post).toHaveBeenCalledWith(`/children/${childId}/stock-decrement`, request, undefined);
		});

		it('should handle decrement value validation', async () => {
			const childId = '1';
			const request: DecrementStockRequest = {
				clothing_category_id: 1,
				decrement: 0 // Invalid decrement
			};

			mockApiClient.post.mockRejectedValueOnce(new Error('Decrement must be positive'));

			await expect(stockApi.decrementStock(childId, request))
				.rejects
				.toThrow('Decrement must be positive');

			expect(mockApiClient.post).toHaveBeenCalledWith(`/children/${childId}/stock-decrement`, request, undefined);
		});
	});
});