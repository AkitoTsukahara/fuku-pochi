import type { Actions } from './$types';
import { fail } from '@sveltejs/kit';
import { stockApi } from '$lib';

export const actions: Actions = {
	incrementStock: async ({ params, request, fetch }) => {
		const formData = await request.formData();
		const categoryId = formData.get('categoryId') as string;
		
		// バリデーション
		if (!categoryId) {
			return fail(400, {
				error: 'カテゴリIDが指定されていません'
			});
		}
		
		try {
			// 在庫増加APIを呼び出し
			await stockApi.incrementStock(params.childId, { categoryId }, fetch);
			
			return {
				success: true,
				action: 'increment',
				categoryId
			};
		} catch (error) {
			console.error('Failed to increment stock:', error);
			return fail(500, {
				error: '在庫の増加に失敗しました。もう一度お試しください。'
			});
		}
	},
	
	decrementStock: async ({ params, request, fetch }) => {
		const formData = await request.formData();
		const categoryId = formData.get('categoryId') as string;
		
		// バリデーション
		if (!categoryId) {
			return fail(400, {
				error: 'カテゴリIDが指定されていません'
			});
		}
		
		try {
			// 在庫減少APIを呼び出し
			await stockApi.decrementStock(params.childId, { categoryId }, fetch);
			
			return {
				success: true,
				action: 'decrement',
				categoryId
			};
		} catch (error) {
			console.error('Failed to decrement stock:', error);
			return fail(500, {
				error: '在庫の減少に失敗しました。もう一度お試しください。'
			});
		}
	}
};