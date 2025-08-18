import { describe, it, expect, beforeEach, vi } from 'vitest';
import { childrenApi } from './children';
import type { Child, UpdateChildRequest } from '$lib/data/types';

// Mock the apiClient
vi.mock('./client', () => ({
	apiClient: {
		get: vi.fn(),
		put: vi.fn(),
		delete: vi.fn()
	}
}));

import { apiClient } from './client';
const mockApiClient = vi.mocked(apiClient);

describe('childrenApi', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	describe('getChild', () => {
		it('should get child successfully', async () => {
			const childId = '1';
			const mockResponse: Child = {
				id: childId,
				name: 'Test Child',
				user_group_id: '1',
				created_at: '2023-01-01T00:00:00Z',
				updated_at: '2023-01-01T00:00:00Z'
			};

			mockApiClient.get.mockResolvedValueOnce(mockResponse);

			const result = await childrenApi.getChild(childId);

			expect(mockApiClient.get).toHaveBeenCalledWith(`/children/${childId}`, undefined);
			expect(result).toEqual(mockResponse);
		});

		it('should pass fetch function to apiClient', async () => {
			const childId = '1';
			const mockResponse: Child = {
				id: childId,
				name: 'Test Child',
				user_group_id: '1'
			};
			const mockFetch = vi.fn();

			mockApiClient.get.mockResolvedValueOnce(mockResponse);

			const result = await childrenApi.getChild(childId, mockFetch);

			expect(mockApiClient.get).toHaveBeenCalledWith(`/children/${childId}`, mockFetch);
			expect(result).toEqual(mockResponse);
		});

		it('should handle child not found', async () => {
			const childId = 'nonexistent';

			mockApiClient.get.mockRejectedValueOnce(new Error('Child not found'));

			await expect(childrenApi.getChild(childId))
				.rejects
				.toThrow('Child not found');

			expect(mockApiClient.get).toHaveBeenCalledWith(`/children/${childId}`, undefined);
		});
	});

	describe('updateChild', () => {
		it('should update child successfully', async () => {
			const childId = '1';
			const request: UpdateChildRequest = { name: 'Updated Name' };
			const mockResponse: Child = {
				id: childId,
				name: 'Updated Name',
				user_group_id: '1',
				updated_at: '2023-01-02T00:00:00Z'
			};

			mockApiClient.put.mockResolvedValueOnce(mockResponse);

			const result = await childrenApi.updateChild(childId, request);

			expect(mockApiClient.put).toHaveBeenCalledWith(`/children/${childId}`, request, undefined);
			expect(result).toEqual(mockResponse);
		});

		it('should pass fetch function to apiClient', async () => {
			const childId = '1';
			const request: UpdateChildRequest = { name: 'Updated Name' };
			const mockResponse: Child = {
				id: childId,
				name: 'Updated Name',
				user_group_id: '1'
			};
			const mockFetch = vi.fn();

			mockApiClient.put.mockResolvedValueOnce(mockResponse);

			const result = await childrenApi.updateChild(childId, request, mockFetch);

			expect(mockApiClient.put).toHaveBeenCalledWith(`/children/${childId}`, request, mockFetch);
			expect(result).toEqual(mockResponse);
		});

		it('should handle validation errors', async () => {
			const childId = '1';
			const request: UpdateChildRequest = { name: '' };

			mockApiClient.put.mockRejectedValueOnce(new Error('Child name is required'));

			await expect(childrenApi.updateChild(childId, request))
				.rejects
				.toThrow('Child name is required');

			expect(mockApiClient.put).toHaveBeenCalledWith(`/children/${childId}`, request, undefined);
		});

		it('should handle child not found during update', async () => {
			const childId = 'nonexistent';
			const request: UpdateChildRequest = { name: 'Updated Name' };

			mockApiClient.put.mockRejectedValueOnce(new Error('Child not found'));

			await expect(childrenApi.updateChild(childId, request))
				.rejects
				.toThrow('Child not found');

			expect(mockApiClient.put).toHaveBeenCalledWith(`/children/${childId}`, request, undefined);
		});
	});

	describe('deleteChild', () => {
		it('should delete child successfully', async () => {
			const childId = '1';

			mockApiClient.delete.mockResolvedValueOnce(undefined);

			const result = await childrenApi.deleteChild(childId);

			expect(mockApiClient.delete).toHaveBeenCalledWith(`/children/${childId}`, undefined);
			expect(result).toBeUndefined();
		});

		it('should pass fetch function to apiClient', async () => {
			const childId = '1';
			const mockFetch = vi.fn();

			mockApiClient.delete.mockResolvedValueOnce(undefined);

			const result = await childrenApi.deleteChild(childId, mockFetch);

			expect(mockApiClient.delete).toHaveBeenCalledWith(`/children/${childId}`, mockFetch);
			expect(result).toBeUndefined();
		});

		it('should handle child not found during deletion', async () => {
			const childId = 'nonexistent';

			mockApiClient.delete.mockRejectedValueOnce(new Error('Child not found'));

			await expect(childrenApi.deleteChild(childId))
				.rejects
				.toThrow('Child not found');

			expect(mockApiClient.delete).toHaveBeenCalledWith(`/children/${childId}`, undefined);
		});

		it('should handle deletion failure', async () => {
			const childId = '1';

			mockApiClient.delete.mockRejectedValueOnce(new Error('Unable to delete child with existing stock items'));

			await expect(childrenApi.deleteChild(childId))
				.rejects
				.toThrow('Unable to delete child with existing stock items');

			expect(mockApiClient.delete).toHaveBeenCalledWith(`/children/${childId}`, undefined);
		});
	});
});