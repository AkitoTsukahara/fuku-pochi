import type { Actions } from './$types';
import { fail } from '@sveltejs/kit';
import { stockApi } from '$lib';

export const actions: Actions = {
	incrementStock: async ({ params, request, fetch }) => {
		const formData = await request.formData();
		const categoryIdString = formData.get('categoryId') as string;
		
		// バリデーション
		if (!categoryIdString) {
			return fail(400, {
				error: 'カテゴリIDが指定されていません'
			});
		}
		
		const categoryId = parseInt(categoryIdString, 10);
		if (isNaN(categoryId)) {
			return fail(400, {
				error: 'カテゴリIDが無効です'
			});
		}
		
		try {
			// 在庫増加APIを呼び出し
			await stockApi.incrementStock(params.childId, { 
				clothing_category_id: categoryId,
				increment: 1 
			}, fetch);
			
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
		const categoryIdString = formData.get('categoryId') as string;
		
		// バリデーション
		if (!categoryIdString) {
			return fail(400, {
				error: 'カテゴリIDが指定されていません'
			});
		}
		
		const categoryId = parseInt(categoryIdString, 10);
		if (isNaN(categoryId)) {
			return fail(400, {
				error: 'カテゴリIDが無効です'
			});
		}
		
		try {
			// 在庫減少APIを呼び出し
			await stockApi.decrementStock(params.childId, { 
				clothing_category_id: categoryId,
				decrement: 1 
			}, fetch);
			
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