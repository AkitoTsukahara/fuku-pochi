import type { PageLoad } from './$types';
import { stockApi, childrenApi, groupsApi } from '$lib';
import { error } from '@sveltejs/kit';

export const load: PageLoad = async ({ params, fetch }) => {
	try {
		// 子ども情報、在庫データ、グループの子ども一覧を並列で取得
		const [child, stockData, allChildren] = await Promise.all([
			childrenApi.getChild(params.childId, fetch),
			stockApi.getStock(params.childId, fetch),
			groupsApi.getGroupChildren(params.token, fetch)
		]);
		
		// stockDataから stock_items 配列とカテゴリ情報を抽出
		const stockItems = stockData.stock_items;
		const categories = stockItems.map(item => item.clothing_category)
			.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
		
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