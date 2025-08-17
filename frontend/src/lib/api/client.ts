import { browser } from '$app/environment';
import type { ApiResponse, ApiErrorResponse } from '$lib/data/types.js';

export class ApiError extends Error {
	public status: number;
	public errors?: Record<string, string[]>;

	constructor(message: string, status: number, errors?: Record<string, string[]>) {
		super(message);
		this.name = 'ApiError';
		this.status = status;
		this.errors = errors;
	}
}

class ApiClient {
	private baseUrl: string;

	constructor() {
		// SSRとクライアントで異なるURLを使用可能
		if (!browser) {
			// サーバーサイド: Docker内部ネットワークやlocalhostを使用
			this.baseUrl = import.meta.env.VITE_API_BASE_URL_SSR || 'http://localhost:8000/api';
		} else {
			// クライアントサイド
			this.baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
		}
	}

	private async request<T>(
		endpoint: string,
		options: RequestInit = {},
		fetchFn: typeof fetch = fetch
	): Promise<T> {
		const url = `${this.baseUrl}${endpoint}`;
		
		const config: RequestInit = {
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json',
				...options.headers
			},
			...options
		};

		try {
			const response = await fetchFn(url, config);
			
			if (!response.ok) {
				const errorData = await response.json() as ApiErrorResponse;
				throw new ApiError(
					errorData.message || `HTTPエラー: ${response.status}`,
					response.status,
					errorData.errors
				);
			}

			const responseData = await response.json() as ApiResponse<T>;
			
			if (!responseData.success) {
				throw new ApiError(
					responseData.message || 'APIエラーが発生しました',
					500
				);
			}

			return responseData.data;
		} catch (error) {
			// ApiErrorはそのまま再スロー
			if (error instanceof ApiError) {
				throw error;
			}

			// ネットワークエラー
			if (error instanceof TypeError && error.message.includes('fetch')) {
				throw new ApiError('ネットワークエラーが発生しました', 0);
			}

			// その他のエラー
			console.error('Unexpected API error:', error);
			throw new ApiError('予期しないエラーが発生しました', 500);
		}
	}

	// SvelteKitのload関数用（fetchを受け取る）
	async get<T>(endpoint: string, fetchFn?: typeof fetch): Promise<T> {
		return this.request<T>(endpoint, { method: 'GET' }, fetchFn);
	}

	async post<T>(endpoint: string, data: any, fetchFn?: typeof fetch): Promise<T> {
		return this.request<T>(endpoint, {
			method: 'POST',
			body: JSON.stringify(data)
		}, fetchFn);
	}

	async put<T>(endpoint: string, data: any, fetchFn?: typeof fetch): Promise<T> {
		return this.request<T>(endpoint, {
			method: 'PUT',
			body: JSON.stringify(data)
		}, fetchFn);
	}

	async delete<T>(endpoint: string, fetchFn?: typeof fetch): Promise<T> {
		return this.request<T>(endpoint, { method: 'DELETE' }, fetchFn);
	}
}

export const apiClient = new ApiClient();