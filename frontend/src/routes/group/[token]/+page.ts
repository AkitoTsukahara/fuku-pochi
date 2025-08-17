import type { PageLoad } from './$types';
import { groupsApi } from '$lib';
import { error } from '@sveltejs/kit';

export const load: PageLoad = async ({ params, fetch }) => {
	try {
		// グループ情報と子ども一覧を並列で取得
		const [group, children] = await Promise.all([
			groupsApi.getGroupByToken(params.token, fetch),
			groupsApi.getGroupChildren(params.token, fetch)
		]);
		
		// グループが作成されたばかりかどうかをチェック
		const isNewGroup = !children || children.length === 0;
		
		// 現在のURLを構築（共有用）
		const shareUrl = typeof window !== 'undefined' 
			? `${window.location.origin}/group/${params.token}`
			: '';
		
		return {
			group,
			children,
			isNewGroup,
			shareUrl,
			token: params.token
		};
	} catch (err) {
		console.error('Failed to load group:', err);
		throw error(404, 'グループが見つかりませんでした');
	}
};