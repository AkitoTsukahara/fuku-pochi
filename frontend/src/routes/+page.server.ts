import type { Actions } from './$types';
import { fail, redirect } from '@sveltejs/kit';
import { groupsApi } from '$lib';

export const actions: Actions = {
	createGroup: async ({ request, fetch }) => {
		const formData = await request.formData();
		const name = formData.get('name') as string;
		
		// バリデーション
		if (!name || name.trim().length === 0) {
			return fail(400, {
				errors: { name: 'グループ名を入力してください' }
			});
		}
		
		if (name.length > 100) {
			return fail(400, {
				errors: { name: 'グループ名は100文字以内で入力してください' }
			});
		}
		
		try {
			// グループ作成APIを呼び出し
			const group = await groupsApi.createGroup({ name: name.trim() }, fetch);
			
			// 作成成功したらグループページにリダイレクト
			throw redirect(303, `/group/${group.share_token}`);
		} catch (error) {
			// リダイレクトの場合はそのまま投げる
			if (error instanceof Response && error.status === 303) {
				throw error;
			}
			
			// エラーの場合
			console.error('Failed to create group:', error);
			return fail(500, {
				error: 'グループの作成に失敗しました。もう一度お試しください。'
			});
		}
	}
};