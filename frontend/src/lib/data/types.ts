// === API Response Types ===

export interface ApiResponse<T> {
	success: boolean;
	message: string;
	data: T;
}

export interface ApiErrorResponse {
	success: false;
	message: string;
	data: null;
	errors?: Record<string, string[]>;
}

// === Entity Types ===

export interface ClothingCategory {
	id: number;
	name: string;
	icon_path?: string;
	sort_order?: number;
}

export interface Child {
	id: string;
	name: string;
	user_group_id: string;
	created_at?: string;
	updated_at?: string;
}

export interface StockItem {
	stock_item_id?: string | null;
	clothing_category_id: number;
	clothing_category: ClothingCategory;
	current_count: number;
}

export interface UserGroup {
	id: string;
	name: string;
	share_token: string;
	children?: Child[];
	created_at?: string;
	updated_at?: string;
}

// === Request Types ===

export interface CreateGroupRequest {
	name: string;
}

export interface CreateChildRequest {
	name: string;
}

export interface UpdateChildRequest {
	name: string;
}

export interface IncrementStockRequest {
	clothing_category_id: number;
	increment: number;
}

export interface DecrementStockRequest {
	clothing_category_id: number;
	decrement: number;
}

// === Page Data Types (for SvelteKit load functions) ===

export interface GroupPageData {
	group: UserGroup;
	children: Child[];
}

export interface StockResponse {
	child_id: string;
	child_name: string;
	stock_items: StockItem[];
}

export interface StockPageData {
	child: Child;
	stockItems: StockItem[];
	categories: ClothingCategory[];
}