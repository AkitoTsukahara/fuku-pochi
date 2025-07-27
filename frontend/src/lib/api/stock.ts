import { apiClient } from './client.js';
import type { StockItem } from '$lib/data/types.js';

export interface UpdateStockRequest {
	childId: string;
	categoryId: string;
	quantity: number;
}

export interface StockResponse {
	data: StockItem[];
	message?: string;
}

export const stockApi = {
	// Get all stock items for a child
	getChildStock: async (childId: string): Promise<StockItem[]> => {
		const response = await apiClient.get<StockResponse>(`/children/${childId}/stock`);
		return response.data;
	},

	// Update stock quantity
	updateStock: async (request: UpdateStockRequest): Promise<StockItem> => {
		const response = await apiClient.put<{ data: StockItem }>(
			`/children/${request.childId}/stock/${request.categoryId}`,
			{ quantity: request.quantity }
		);
		return response.data;
	},

	// Increment stock
	incrementStock: async (childId: string, categoryId: string): Promise<StockItem> => {
		const response = await apiClient.post<{ data: StockItem }>(
			`/children/${childId}/stock/${categoryId}/increment`,
			{}
		);
		return response.data;
	},

	// Decrement stock
	decrementStock: async (childId: string, categoryId: string): Promise<StockItem> => {
		const response = await apiClient.post<{ data: StockItem }>(
			`/children/${childId}/stock/${categoryId}/decrement`,
			{}
		);
		return response.data;
	}
};