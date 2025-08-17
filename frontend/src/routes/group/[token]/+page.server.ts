import type { Actions } from './$types';
import { fail } from '@sveltejs/kit';
import { groupsApi } from '$lib';

export const actions: Actions = {
	addChild: async ({ params, request, fetch }) => {
		const formData = await request.formData();
		const name = formData.get('name') as string;
		
		// バリデーション
		if (!name || name.trim().length === 0) {
			return fail(400, {
				errors: { name: '子どもの名前を入力してください' }
			});
		}
		
		if (name.length > 100) {
			return fail(400, {
				errors: { name: '名前は100文字以内で入力してください' }
			});
		}
		
		try {
			// 子ども追加APIを呼び出し
			await groupsApi.createChild(params.token, { name: name.trim() }, fetch);
			
			return {
				success: true,
				message: `${name.trim()}さんを追加しました`
			};
		} catch (error) {
			console.error('Failed to add child:', error);
			return fail(500, {
				error: '子どもの追加に失敗しました。もう一度お試しください。'
			});
		}
	}
};