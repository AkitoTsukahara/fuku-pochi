// SvelteKitのload関数での使用例

import { error } from '@sveltejs/kit';
import type { PageLoad, PageServerLoad } from './$types';
import { 
	groupsApi, 
	childrenApi,
	stockApi,
	categoriesApi,
	ApiError 
} from '$lib';

// ==============================
// クライアントサイドload関数の例
// ==============================

// グループ詳細ページ
export const loadGroupPage: PageLoad = async ({ params, fetch }) => {
	try {
		// 並列でデータを取得
		const [group, children] = await Promise.all([
			groupsApi.getGroupByToken(params.token, fetch),
			groupsApi.getGroupChildren(params.token, fetch)
		]);

		return {
			group,
			children
		};
	} catch (err) {
		if (err instanceof ApiError) {
			throw error(err.status, err.message);
		}
		throw error(500, '予期しないエラーが発生しました');
	}
};

// 在庫管理ページ
export const loadStockPage: PageLoad = async ({ params, fetch, parent }) => {
	try {
		// 親ルートのデータを取得
		const parentData = await parent();
		
		// 並列でデータを取得
		const [stockItems, categories] = await Promise.all([
			stockApi.getChildStock(params.childId, fetch),
			categoriesApi.getClothingCategories(fetch)
		]);

		return {
			...parentData,
			child: parentData.children.find((c: any) => c.id === params.childId),
			stockItems,
			categories
		};
	} catch (err) {
		if (err instanceof ApiError) {
			throw error(err.status, err.message);
		}
		throw error(500, '予期しないエラーが発生しました');
	}
};

// ==============================
// サーバーサイドload関数の例
// ==============================

// SSRでのグループ詳細ページ
export const loadGroupPageSSR: PageServerLoad = async ({ params, fetch }) => {
	try {
		// サーバーサイドでも同じAPIクライアントが使える
		const [group, children] = await Promise.all([
			groupsApi.getGroupByToken(params.token, fetch),
			groupsApi.getGroupChildren(params.token, fetch)
		]);

		return {
			group,
			children
		};
	} catch (err) {
		if (err instanceof ApiError) {
			throw error(err.status, err.message);
		}
		throw error(500, 'サーバーエラーが発生しました');
	}
};

// ==============================
// Form Actionsでの使用例
// ==============================

import type { Actions } from './$types';
import { fail, redirect } from '@sveltejs/kit';

export const formActions: Actions = {
	// グループ作成
	createGroup: async ({ request, fetch }) => {
		const formData = await request.formData();
		const name = formData.get('name') as string;

		if (!name) {
			return fail(400, { 
				error: 'グループ名は必須です' 
			});
		}

		try {
			const group = await groupsApi.createGroup({ name }, fetch);
			throw redirect(303, `/groups/${group.share_token}`);
		} catch (err) {
			if (err instanceof ApiError) {
				return fail(err.status, { 
					error: err.message 
				});
			}
			return fail(500, { 
				error: '予期しないエラーが発生しました' 
			});
		}
	},

	// 子ども追加
	addChild: async ({ params, request, fetch }) => {
		const formData = await request.formData();
		const name = formData.get('name') as string;

		if (!name) {
			return fail(400, { 
				error: '子どもの名前は必須です' 
			});
		}

		try {
			await groupsApi.createChild(params.token, { name }, fetch);
			// データを再フェッチするためのinvalidateを実行
			return { success: true };
		} catch (err) {
			if (err instanceof ApiError) {
				return fail(err.status, { 
					error: err.message 
				});
			}
			return fail(500, { 
				error: '予期しないエラーが発生しました' 
			});
		}
	},

	// 在庫増加
	incrementStock: async ({ params, request, fetch }) => {
		const formData = await request.formData();
		const categoryId = formData.get('categoryId') as string;

		try {
			await stockApi.incrementStock(
				params.childId,
				{ 
					clothing_category_id: categoryId,
					increment: 1 
				},
				fetch
			);
			// 成功したらデータを再フェッチ
			return { success: true };
		} catch (err) {
			if (err instanceof ApiError) {
				return fail(err.status, { 
					error: err.message 
				});
			}
			return fail(500, { 
				error: '予期しないエラーが発生しました' 
			});
		}
	}
};