import { apiClient } from './client.js';
import type { 
	UserGroup, 
	Child, 
	CreateGroupRequest,
	CreateChildRequest
} from '$lib/data/types.js';

export const groupsApi = {
	// Create a new group
	createGroup: async (request: CreateGroupRequest, fetch?: typeof window.fetch): Promise<UserGroup> => {
		return apiClient.post<UserGroup>('/groups', request, fetch);
	},

	// Get group by share token (for load functions)
	getGroupByToken: async (token: string, fetch?: typeof window.fetch): Promise<UserGroup> => {
		return apiClient.get<UserGroup>(`/groups/${token}`, fetch);
	},

	// Get group children by token (for load functions)
	getGroupChildren: async (token: string, fetch?: typeof window.fetch): Promise<Child[]> => {
		return apiClient.get<Child[]>(`/groups/${token}/children`, fetch);
	},

	// Create a child in a group
	createChild: async (token: string, request: CreateChildRequest, fetch?: typeof window.fetch): Promise<Child> => {
		return apiClient.post<Child>(`/groups/${token}/children`, request, fetch);
	}
};