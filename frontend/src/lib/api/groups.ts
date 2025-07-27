import { apiClient } from './client.js';
import type { UserGroup, Child } from '$lib/data/types.js';

export interface CreateGroupRequest {
	name: string;
}

export interface CreateChildRequest {
	name: string;
	groupId: string;
}

export interface GroupResponse {
	data: UserGroup;
	message?: string;
}

export interface ChildrenResponse {
	data: Child[];
	message?: string;
}

export interface ChildResponse {
	data: Child;
	message?: string;
}

export const groupsApi = {
	// Create a new group
	createGroup: async (request: CreateGroupRequest): Promise<UserGroup> => {
		const response = await apiClient.post<GroupResponse>('/groups', request);
		return response.data;
	},

	// Get group by ID
	getGroup: async (groupId: string): Promise<UserGroup> => {
		const response = await apiClient.get<GroupResponse>(`/groups/${groupId}`);
		return response.data;
	},

	// Get group children
	getGroupChildren: async (groupId: string): Promise<Child[]> => {
		const response = await apiClient.get<ChildrenResponse>(`/groups/${groupId}/children`);
		return response.data;
	},

	// Create a child in a group
	createChild: async (request: CreateChildRequest): Promise<Child> => {
		const response = await apiClient.post<ChildResponse>(
			`/groups/${request.groupId}/children`,
			{ name: request.name }
		);
		return response.data;
	},

	// Delete a child
	deleteChild: async (childId: string): Promise<void> => {
		await apiClient.delete(`/children/${childId}`);
	}
};