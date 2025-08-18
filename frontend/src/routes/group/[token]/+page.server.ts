import type { Actions } from './$types';
import { fail } from '@sveltejs/kit';
import { groupsApi, childrenApi } from '$lib';

export const actions: Actions = {
	addChild: async ({ params, request, fetch }) => {
		const formData = await request.formData();
		const name = formData.get('name') as string;
		
		// バリデーション
		if (!name || name.trim().length === 0) {
			return fail(400, {
				errors: { name: 'お子さまの名前を入力してください' }
			});
		}
		
		if (name.length > 100) {
			return fail(400, {
				errors: { name: '名前は100文字以内で入力してください' }
			});
		}
		
		try {
			// お子さま追加APIを呼び出し
			await groupsApi.createChild(params.token, { name: name.trim() }, fetch);
			
			return {
				success: true,
				message: `${name.trim()}さんを追加しました`
			};
		} catch (error) {
			console.error('Failed to add child:', error);
			return fail(500, {
				error: 'お子さまの追加に失敗しました。もう一度お試しください。'
			});
		}
	},
	
	updateChild: async ({ request, fetch }) => {
		const formData = await request.formData();
		const childId = formData.get('childId') as string;
		const name = formData.get('name') as string;
		
		// バリデーション
		if (!childId) {
			return fail(400, {
				error: 'お子さまIDが指定されていません'
			});
		}
		
		if (!name || name.trim().length === 0) {
			return fail(400, {
				errors: { name: 'お子さまの名前を入力してください' }
			});
		}
		
		if (name.length > 100) {
			return fail(400, {
				errors: { name: '名前は100文字以内で入力してください' }
			});
		}
		
		try {
			// お子さま更新APIを呼び出し
			await childrenApi.updateChild(childId, { name: name.trim() }, fetch);
			
			return {
				success: true,
				message: `${name.trim()}さんの名前を更新しました`
			};
		} catch (error) {
			console.error('Failed to update child:', error);
			return fail(500, {
				error: 'お子さまの名前の更新に失敗しました。もう一度お試しください。'
			});
		}
	},
	
	deleteChild: async ({ request, fetch }) => {
		const formData = await request.formData();
		const childId = formData.get('childId') as string;
		
		// バリデーション
		if (!childId) {
			return fail(400, {
				error: 'お子さまIDが指定されていません'
			});
		}
		
		try {
			// お子さま削除APIを呼び出し
			await childrenApi.deleteChild(childId, fetch);
			
			return {
				success: true,
				message: 'お子さまを削除しました'
			};
		} catch (error) {
			console.error('Failed to delete child:', error);
			return fail(500, {
				error: 'お子さまの削除に失敗しました。もう一度お試しください。'
			});
		}
	}
};