import { describe, it, expect, beforeEach, vi } from 'vitest';
import { apiClient, ApiError } from './client';
import type { ApiResponse, ApiErrorResponse } from '$lib/data/types';

describe('ApiError', () => {
	it('should create ApiError with message and status', () => {
		const error = new ApiError('Test error', 400);
		
		expect(error.name).toBe('ApiError');
		expect(error.message).toBe('Test error');
		expect(error.status).toBe(400);
		expect(error.errors).toBeUndefined();
	});

	it('should create ApiError with validation errors', () => {
		const errors = { name: ['名前は必須です'] };
		const error = new ApiError('Validation error', 422, errors);
		
		expect(error.name).toBe('ApiError');
		expect(error.message).toBe('Validation error');
		expect(error.status).toBe(422);
		expect(error.errors).toEqual(errors);
	});
});

describe('ApiClient', () => {
	let mockFetch: any;

	beforeEach(() => {
		mockFetch = vi.fn();
		vi.clearAllMocks();
	});

	describe('GET requests', () => {
		it('should make successful GET request', async () => {
			const responseData = { id: 1, name: 'Test' };
			const apiResponse: ApiResponse<typeof responseData> = {
				success: true,
				message: 'Success',
				data: responseData
			};

			mockFetch.mockResolvedValueOnce({
				ok: true,
				json: vi.fn().mockResolvedValueOnce(apiResponse)
			});

			const result = await apiClient.get('/test', mockFetch);

			expect(mockFetch).toHaveBeenCalledWith(
				'http://localhost:8000/api/test',
				{
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
						'Accept': 'application/json'
					}
				}
			);
			expect(result).toEqual(responseData);
		});

		it('should handle HTTP error response', async () => {
			const errorResponse: ApiErrorResponse = {
				success: false,
				message: 'Not found',
				data: null
			};

			mockFetch.mockResolvedValueOnce({
				ok: false,
				status: 404,
				json: vi.fn().mockResolvedValueOnce(errorResponse)
			});

			await expect(apiClient.get('/test', mockFetch))
				.rejects
				.toThrow(ApiError);

			try {
				await apiClient.get('/test', mockFetch);
			} catch (error) {
				expect(error).toBeInstanceOf(ApiError);
				expect((error as ApiError).status).toBe(404);
				expect((error as ApiError).message).toBe('Not found');
			}
		});

		it('should handle validation errors', async () => {
			const errorResponse: ApiErrorResponse = {
				success: false,
				message: 'Validation failed',
				data: null,
				errors: {
					name: ['名前は必須です'],
					email: ['メールアドレスは必須です']
				}
			};

			mockFetch.mockResolvedValueOnce({
				ok: false,
				status: 422,
				json: vi.fn().mockResolvedValueOnce(errorResponse)
			});

			try {
				await apiClient.get('/test', mockFetch);
				expect.fail('Should have thrown ApiError');
			} catch (error) {
				expect(error).toBeInstanceOf(ApiError);
				expect((error as ApiError).status).toBe(422);
				expect((error as ApiError).message).toBe('Validation failed');
				expect((error as ApiError).errors).toEqual(errorResponse.errors);
			}
		});

		it('should handle API response with success: false', async () => {
			const apiResponse: ApiResponse<any> = {
				success: false,
				message: 'Internal server error',
				data: null
			};

			mockFetch.mockResolvedValueOnce({
				ok: true,
				json: vi.fn().mockResolvedValueOnce(apiResponse)
			});

			try {
				await apiClient.get('/test', mockFetch);
				expect.fail('Should have thrown ApiError');
			} catch (error) {
				expect(error).toBeInstanceOf(ApiError);
				expect((error as ApiError).status).toBe(500);
				expect((error as ApiError).message).toBe('Internal server error');
			}
		});

		it('should handle network errors', async () => {
			mockFetch.mockRejectedValueOnce(new TypeError('fetch is not available'));

			try {
				await apiClient.get('/test', mockFetch);
				expect.fail('Should have thrown ApiError');
			} catch (error) {
				expect(error).toBeInstanceOf(ApiError);
				expect((error as ApiError).status).toBe(0);
				expect((error as ApiError).message).toBe('ネットワークエラーが発生しました');
			}
		});
	});

	describe('POST requests', () => {
		it('should make successful POST request with data', async () => {
			const requestData = { name: 'Test Group' };
			const responseData = { id: 1, name: 'Test Group', token: 'abc123' };
			const apiResponse: ApiResponse<typeof responseData> = {
				success: true,
				message: 'Created successfully',
				data: responseData
			};

			mockFetch.mockResolvedValueOnce({
				ok: true,
				json: vi.fn().mockResolvedValueOnce(apiResponse)
			});

			const result = await apiClient.post('/groups', requestData, mockFetch);

			expect(mockFetch).toHaveBeenCalledWith(
				'http://localhost:8000/api/groups',
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'Accept': 'application/json'
					},
					body: JSON.stringify(requestData)
				}
			);
			expect(result).toEqual(responseData);
		});
	});

	describe('PUT requests', () => {
		it('should make successful PUT request with data', async () => {
			const requestData = { name: 'Updated Name' };
			const responseData = { id: 1, name: 'Updated Name' };
			const apiResponse: ApiResponse<typeof responseData> = {
				success: true,
				message: 'Updated successfully',
				data: responseData
			};

			mockFetch.mockResolvedValueOnce({
				ok: true,
				json: vi.fn().mockResolvedValueOnce(apiResponse)
			});

			const result = await apiClient.put('/children/1', requestData, mockFetch);

			expect(mockFetch).toHaveBeenCalledWith(
				'http://localhost:8000/api/children/1',
				{
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json',
						'Accept': 'application/json'
					},
					body: JSON.stringify(requestData)
				}
			);
			expect(result).toEqual(responseData);
		});
	});

	describe('DELETE requests', () => {
		it('should make successful DELETE request', async () => {
			const apiResponse: ApiResponse<void> = {
				success: true,
				message: 'Deleted successfully',
				data: undefined as any
			};

			mockFetch.mockResolvedValueOnce({
				ok: true,
				json: vi.fn().mockResolvedValueOnce(apiResponse)
			});

			const result = await apiClient.delete('/children/1', mockFetch);

			expect(mockFetch).toHaveBeenCalledWith(
				'http://localhost:8000/api/children/1',
				{
					method: 'DELETE',
					headers: {
						'Content-Type': 'application/json',
						'Accept': 'application/json'
					}
				}
			);
			expect(result).toBeUndefined();
		});
	});
});