export interface ClothingCategory {
	id: string;
	name: string;
	iconPath?: string;
}

export interface Child {
	id: string;
	name: string;
	groupId: string;
}

export interface StockItem {
	categoryId: string;
	quantity: number;
	updatedAt?: string;
}

export interface UserGroup {
	id: string;
	name: string;
	shareUrl?: string;
}