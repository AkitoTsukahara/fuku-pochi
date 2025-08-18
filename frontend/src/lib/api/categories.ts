import { apiClient } from './client.js';
import type { ClothingCategory } from '$lib/data/types.js';

export const categoriesApi = {
	// Get all clothing categories (for load functions)
	getClothingCategories: async (fetch?: typeof window.fetch): Promise<ClothingCategory[]> => {
		return apiClient.get<ClothingCategory[]>('/clothing-categories', fetch);
	},

	// Alias for getClothingCategories (for backward compatibility)
	getCategories: async (fetch?: typeof window.fetch): Promise<ClothingCategory[]> => {
		return apiClient.get<ClothingCategory[]>('/clothing-categories', fetch);
	}
};