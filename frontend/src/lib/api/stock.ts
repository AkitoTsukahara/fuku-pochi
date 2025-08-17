import { apiClient } from './client.js';
import type { 
	StockItem, 
	IncrementStockRequest,
	DecrementStockRequest
} from '$lib/data/types.js';

export const stockApi = {
	// Get all stock items for a child (for load functions)
	getChildStock: async (childId: string, fetch?: typeof window.fetch): Promise<StockItem[]> => {
		return apiClient.get<StockItem[]>(`/children/${childId}/stock`, fetch);
	},

	// Increment stock
	incrementStock: async (childId: string, request: IncrementStockRequest, fetch?: typeof window.fetch): Promise<StockItem> => {
		return apiClient.post<StockItem>(`/children/${childId}/stock-increment`, request, fetch);
	},

	// Decrement stock
	decrementStock: async (childId: string, request: DecrementStockRequest, fetch?: typeof window.fetch): Promise<StockItem> => {
		return apiClient.post<StockItem>(`/children/${childId}/stock-decrement`, request, fetch);
	}
};