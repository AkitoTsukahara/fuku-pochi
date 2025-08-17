import type { PageLoad } from './$types';
import { stockApi, childrenApi, categoriesApi, groupsApi } from '$lib';
import { error } from '@sveltejs/kit';

export const load: PageLoad = async ({ params, fetch }) => {
	try {
		// 子ども情報、在庫データ、カテゴリ一覧、グループの子ども一覧を並列で取得
		const [child, stockItems, categories, allChildren] = await Promise.all([
			childrenApi.getChild(params.childId, fetch),
			stockApi.getStock(params.childId, fetch),
			categoriesApi.getCategories(fetch),
			groupsApi.getGroupChildren(params.token, fetch)
		]);
		
		return {
			child,
			stockItems,
			categories,
			allChildren,
			token: params.token,
			childId: params.childId
		};
	} catch (err) {
		console.error('Failed to load child stock data:', err);
		throw error(404, '子どもまたは在庫データが見つかりませんでした');
	}
};