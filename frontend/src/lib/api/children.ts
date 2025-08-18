import { apiClient } from './client.js';
import type { 
	Child, 
	UpdateChildRequest
} from '$lib/data/types.js';

export const childrenApi = {
	// Get a specific child
	getChild: async (childId: string, fetch?: typeof window.fetch): Promise<Child> => {
		return apiClient.get<Child>(`/children/${childId}`, fetch);
	},

	// Update a child
	updateChild: async (childId: string, request: UpdateChildRequest, fetch?: typeof window.fetch): Promise<Child> => {
		return apiClient.put<Child>(`/children/${childId}`, request, fetch);
	},

	// Delete a child
	deleteChild: async (childId: string, fetch?: typeof window.fetch): Promise<void> => {
		return apiClient.delete<void>(`/children/${childId}`, fetch);
	}
};