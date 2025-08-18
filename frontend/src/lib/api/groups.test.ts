import { describe, it, expect, beforeEach, vi } from 'vitest';
import { groupsApi } from './groups';
import type { UserGroup, Child, CreateGroupRequest, CreateChildRequest } from '$lib/data/types';

// Mock the apiClient
vi.mock('./client', () => ({
	apiClient: {
		post: vi.fn(),
		get: vi.fn()
	}
}));

import { apiClient } from './client';
const mockApiClient = vi.mocked(apiClient);

describe('groupsApi', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	describe('createGroup', () => {
		it('should create a group successfully', async () => {
			const request: CreateGroupRequest = { name: 'Test Group' };
			const mockResponse: UserGroup = {
				id: '1',
				name: 'Test Group',
				share_token: 'abc123',
				children: []
			};

			mockApiClient.post.mockResolvedValueOnce(mockResponse);

			const result = await groupsApi.createGroup(request);

			expect(mockApiClient.post).toHaveBeenCalledWith('/groups', request, undefined);
			expect(result).toEqual(mockResponse);
		});

		it('should pass fetch function to apiClient', async () => {
			const request: CreateGroupRequest = { name: 'Test Group' };
			const mockResponse: UserGroup = {
				id: '1',
				name: 'Test Group',
				share_token: 'abc123',
				children: []
			};
			const mockFetch = vi.fn();

			mockApiClient.post.mockResolvedValueOnce(mockResponse);

			const result = await groupsApi.createGroup(request, mockFetch);

			expect(mockApiClient.post).toHaveBeenCalledWith('/groups', request, mockFetch);
			expect(result).toEqual(mockResponse);
		});

		it('should handle API errors', async () => {
			const request: CreateGroupRequest = { name: '' };

			mockApiClient.post.mockRejectedValueOnce(new Error('Validation error'));

			await expect(groupsApi.createGroup(request))
				.rejects
				.toThrow('Validation error');

			expect(mockApiClient.post).toHaveBeenCalledWith('/groups', request, undefined);
		});
	});

	describe('getGroupByToken', () => {
		it('should get group by token successfully', async () => {
			const token = 'abc123';
			const mockResponse: UserGroup = {
				id: '1',
				name: 'Test Group',
				share_token: token,
				children: [
					{
						id: '1',
						name: 'Child 1',
						user_group_id: '1'
					}
				]
			};

			mockApiClient.get.mockResolvedValueOnce(mockResponse);

			const result = await groupsApi.getGroupByToken(token);

			expect(mockApiClient.get).toHaveBeenCalledWith(`/groups/${token}`, undefined);
			expect(result).toEqual(mockResponse);
		});

		it('should pass fetch function to apiClient', async () => {
			const token = 'abc123';
			const mockResponse: UserGroup = {
				id: '1',
				name: 'Test Group',
				share_token: token,
				children: []
			};
			const mockFetch = vi.fn();

			mockApiClient.get.mockResolvedValueOnce(mockResponse);

			const result = await groupsApi.getGroupByToken(token, mockFetch);

			expect(mockApiClient.get).toHaveBeenCalledWith(`/groups/${token}`, mockFetch);
			expect(result).toEqual(mockResponse);
		});

		it('should handle group not found', async () => {
			const token = 'invalid-token';

			mockApiClient.get.mockRejectedValueOnce(new Error('Group not found'));

			await expect(groupsApi.getGroupByToken(token))
				.rejects
				.toThrow('Group not found');

			expect(mockApiClient.get).toHaveBeenCalledWith(`/groups/${token}`, undefined);
		});
	});

	describe('getGroupChildren', () => {
		it('should get group children successfully', async () => {
			const token = 'abc123';
			const mockResponse: Child[] = [
				{
					id: '1',
					name: 'Child 1',
					user_group_id: '1'
				},
				{
					id: '2',
					name: 'Child 2',
					user_group_id: '1'
				}
			];

			mockApiClient.get.mockResolvedValueOnce(mockResponse);

			const result = await groupsApi.getGroupChildren(token);

			expect(mockApiClient.get).toHaveBeenCalledWith(`/groups/${token}/children`, undefined);
			expect(result).toEqual(mockResponse);
		});

		it('should return empty array when group has no children', async () => {
			const token = 'abc123';
			const mockResponse: Child[] = [];

			mockApiClient.get.mockResolvedValueOnce(mockResponse);

			const result = await groupsApi.getGroupChildren(token);

			expect(mockApiClient.get).toHaveBeenCalledWith(`/groups/${token}/children`, undefined);
			expect(result).toEqual([]);
		});

		it('should pass fetch function to apiClient', async () => {
			const token = 'abc123';
			const mockResponse: Child[] = [];
			const mockFetch = vi.fn();

			mockApiClient.get.mockResolvedValueOnce(mockResponse);

			const result = await groupsApi.getGroupChildren(token, mockFetch);

			expect(mockApiClient.get).toHaveBeenCalledWith(`/groups/${token}/children`, mockFetch);
			expect(result).toEqual(mockResponse);
		});
	});

	describe('createChild', () => {
		it('should create child in group successfully', async () => {
			const token = 'abc123';
			const request: CreateChildRequest = { name: 'New Child' };
			const mockResponse: Child = {
				id: '3',
				name: 'New Child',
				user_group_id: '1'
			};

			mockApiClient.post.mockResolvedValueOnce(mockResponse);

			const result = await groupsApi.createChild(token, request);

			expect(mockApiClient.post).toHaveBeenCalledWith(`/groups/${token}/children`, request, undefined);
			expect(result).toEqual(mockResponse);
		});

		it('should pass fetch function to apiClient', async () => {
			const token = 'abc123';
			const request: CreateChildRequest = { name: 'New Child' };
			const mockResponse: Child = {
				id: '3',
				name: 'New Child',
				user_group_id: '1'
			};
			const mockFetch = vi.fn();

			mockApiClient.post.mockResolvedValueOnce(mockResponse);

			const result = await groupsApi.createChild(token, request, mockFetch);

			expect(mockApiClient.post).toHaveBeenCalledWith(`/groups/${token}/children`, request, mockFetch);
			expect(result).toEqual(mockResponse);
		});

		it('should handle validation errors', async () => {
			const token = 'abc123';
			const request: CreateChildRequest = { name: '' };

			mockApiClient.post.mockRejectedValueOnce(new Error('Child name is required'));

			await expect(groupsApi.createChild(token, request))
				.rejects
				.toThrow('Child name is required');

			expect(mockApiClient.post).toHaveBeenCalledWith(`/groups/${token}/children`, request, undefined);
		});
	});
});