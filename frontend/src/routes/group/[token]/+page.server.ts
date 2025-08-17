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
	},
	
	updateChild: async ({ request, fetch }) => {
		const formData = await request.formData();
		const childId = formData.get('childId') as string;
		const name = formData.get('name') as string;
		
		// バリデーション
		if (!childId) {
			return fail(400, {
				error: '子どもIDが指定されていません'
			});
		}
		
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
			// 子ども更新APIを呼び出し
			await childrenApi.updateChild(childId, { name: name.trim() }, fetch);
			
			return {
				success: true,
				message: `${name.trim()}さんの名前を更新しました`
			};
		} catch (error) {
			console.error('Failed to update child:', error);
			return fail(500, {
				error: '子どもの名前の更新に失敗しました。もう一度お試しください。'
			});
		}
	},
	
	deleteChild: async ({ request, fetch }) => {
		const formData = await request.formData();
		const childId = formData.get('childId') as string;
		
		// バリデーション
		if (!childId) {
			return fail(400, {
				error: '子どもIDが指定されていません'
			});
		}
		
		try {
			// 子ども削除APIを呼び出し
			await childrenApi.deleteChild(childId, fetch);
			
			return {
				success: true,
				message: '子どもを削除しました'
			};
		} catch (error) {
			console.error('Failed to delete child:', error);
			return fail(500, {
				error: '子どもの削除に失敗しました。もう一度お試しください。'
			});
		}
	}
};