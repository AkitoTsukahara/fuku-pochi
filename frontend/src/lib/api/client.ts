import { browser } from '$app/environment';

class ApiClient {
	private baseUrl: string;

	constructor() {
		this.baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
	}

	private async request<T>(
		endpoint: string,
		options: RequestInit = {}
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
			const response = await fetch(url, config);
			
			if (!response.ok) {
				throw new Error(`API Error: ${response.status} ${response.statusText}`);
			}

			const data = await response.json();
			return data;
		} catch (error) {
			console.error('API Request failed:', error);
			throw error;
		}
	}

	async get<T>(endpoint: string): Promise<T> {
		return this.request<T>(endpoint, { method: 'GET' });
	}

	async post<T>(endpoint: string, data: any): Promise<T> {
		return this.request<T>(endpoint, {
			method: 'POST',
			body: JSON.stringify(data)
		});
	}

	async put<T>(endpoint: string, data: any): Promise<T> {
		return this.request<T>(endpoint, {
			method: 'PUT',
			body: JSON.stringify(data)
		});
	}

	async delete<T>(endpoint: string): Promise<T> {
		return this.request<T>(endpoint, { method: 'DELETE' });
	}
}

export const apiClient = new ApiClient();