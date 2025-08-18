import { test, expect } from '@playwright/test';

test.describe('Main User Flow - Group and Stock Management', () => {
	let shareToken: string;

	test('should complete the main user flow', async ({ page }) => {
		// 1. トップページにアクセス
		await page.goto('/');
		
		// ページタイトルの確認
		await expect(page).toHaveTitle(/ふくぽち/);
		
		// 2. グループを作成
		await page.fill('input[name="name"]', 'テストグループ2025');
		await page.click('button:has-text("グループを作成")');
		
		// グループ作成成功の確認
		await page.waitForURL(/\/group\/.+/);
		const url = page.url();
		const match = url.match(/\/group\/([^/]+)/);
		shareToken = match ? match[1] : '';
		
		expect(shareToken).toBeTruthy();
		
		// グループ名が表示されているか確認
		await expect(page.locator('h2')).toContainText('テストグループ2025');
		
		// 3. 子どもを追加
		await page.click('text=お子さまを追加');
		await page.fill('input[name="name"]', '太郎');
		await page.locator('.modal-content').locator('button:has-text("追加")').click();
		
		// 子どもが追加されたことを確認
		await expect(page.locator('h4:has-text("太郎")')).toBeVisible();
		
		// 4. 別の子どもも追加
		await page.click('text=お子さまを追加');
		await page.fill('input[name="name"]', '花子');
		await page.locator('.modal-content').locator('button:has-text("追加")').click();
		
		// 複数の子どもが表示されているか確認
		await expect(page.locator('h4:has-text("太郎")')).toBeVisible();
		await expect(page.locator('h4:has-text("花子")')).toBeVisible();
		
		// 5. 太郎のストック管理画面へ
		await page.click('h4:has-text("太郎")');
		await page.waitForURL(/\/child\/.+/);
		
		// ストック管理画面の表示確認
		await expect(page.locator('.header__subtitle')).toContainText('太郎');
		
		// 6. ストックを増やす
		// 最初のストックカードを選択して操作
		const firstStockCard = page.locator('.stock-card').first();
		await firstStockCard.locator('button:has-text("＋")').click();
		await expect(firstStockCard.locator('.stock-count')).toContainText('1');
		
		// もう一度増やす
		await firstStockCard.locator('button:has-text("＋")').click();
		await expect(firstStockCard.locator('.stock-count')).toContainText('2');
		
		// 7. ストックを減らす
		await firstStockCard.locator('button:has-text("−")').click();
		await expect(firstStockCard.locator('.stock-count')).toContainText('1');
		
		// 8. 戻るボタンでグループ画面へ
		await page.click('button:has-text("戻る")');
		await page.waitForURL(/\/group\/.+/);
		
		// グループ画面に戻ったことを確認
		await expect(page.locator('h4:has-text("太郎")')).toBeVisible();
		await expect(page.locator('h4:has-text("花子")')).toBeVisible();
		
		// 9. 花子のストック管理画面へ
		await page.click('h4:has-text("花子")');
		await page.waitForURL(/\/child\/.+/);
		
		// 花子のストック管理画面の表示確認
		await expect(page.locator('.header__subtitle')).toContainText('花子');
		
		// 10. 複数カテゴリのストックを管理
		const secondStockCard = page.locator('.stock-card').nth(1);
		await secondStockCard.locator('button:has-text("＋")').click();
		await secondStockCard.locator('button:has-text("＋")').click();
		await secondStockCard.locator('button:has-text("＋")').click();
		await expect(secondStockCard.locator('.stock-count')).toContainText('3');
		
		const thirdStockCard = page.locator('.stock-card').nth(2);
		await thirdStockCard.locator('button:has-text("＋")').click();
		await expect(thirdStockCard.locator('.stock-count')).toContainText('1');
	});

	test('should share URL and access group', async ({ page, context }) => {
		// 最初にグループを作成
		await page.goto('/');
		await page.fill('input[name="name"]', '共有テストグループ');
		await page.click('button:has-text("グループを作成")');
		
		await page.waitForURL(/\/group\/.+/);
		const url = page.url();
		
		// URLをコピー（実際のアプリケーションではコピーボタンがあるはず）
		const shareUrl = url;
		
		// 新しいタブ/ウィンドウでURLにアクセス
		const newPage = await context.newPage();
		await newPage.goto(shareUrl);
		
		// 同じグループにアクセスできることを確認
		await expect(newPage.locator('h2')).toContainText('共有テストグループ');
		
		// 新しいタブで子どもを追加
		await newPage.click('text=お子さまを追加');
		await newPage.fill('input[name="name"]', '次郎');
		await newPage.locator('.modal-content').locator('button:has-text("追加")').click();
		
		// 元のページをリロード（少し待ってから）
		await page.waitForTimeout(1000);
		await page.reload();
		await page.waitForTimeout(1000);
		
		// 新しく追加された子どもが表示されることを確認
		await expect(page.locator('h4:has-text("次郎")').or(page.locator('text=次郎'))).toBeVisible();
	});

	test('should persist stock data', async ({ page }) => {
		// グループを作成
		await page.goto('/');
		await page.fill('input[name="name"]', '永続化テストグループ');
		await page.click('button:has-text("グループを作成")');
		
		await page.waitForURL(/\/group\/.+/);
		const groupUrl = page.url();
		
		// 子どもを追加
		await page.click('text=お子さまを追加');
		await page.fill('input[name="name"]', '三郎');
		await page.locator('.modal-content').locator('button:has-text("追加")').click();
		
		// ストック管理画面へ
		await page.click('h4:has-text("三郎")');
		await page.waitForURL(/\/child\/.+/);
		
		// ストックを設定
		const stockCard = page.locator('.stock-card').first();
		await stockCard.locator('button:has-text("＋")').click();
		await stockCard.locator('button:has-text("＋")').click();
		await stockCard.locator('button:has-text("＋")').click();
		await expect(stockCard.locator('.stock-count')).toContainText('3');
		
		// ページをリロード
		await page.reload();
		
		// ストック数が保持されていることを確認
		const stockCardAfterReload = page.locator('.stock-card').first();
		await expect(stockCardAfterReload.locator('.stock-count')).toContainText('3');
		
		// グループ画面に戻る
		await page.goto(groupUrl);
		
		// 再度ストック管理画面へ
		await page.click('h4:has-text("三郎")');
		await page.waitForURL(/\/child\/.+/);
		
		// ストック数が保持されていることを確認
		const stockCardAgain = page.locator('.stock-card').first();
		await expect(stockCardAgain.locator('.stock-count')).toContainText('3');
	});

	test('should handle edge cases', async ({ page }) => {
		// グループを作成
		await page.goto('/');
		await page.fill('input[name="name"]', 'エッジケーステスト');
		await page.click('button:has-text("グループを作成")');
		
		await page.waitForURL(/\/group\/.+/);
		
		// 長い名前の子どもを追加
		await page.click('text=お子さまを追加');
		await page.fill('input[name="name"]', 'あいうえおかきくけこさしすせそたちつてと');
		await page.locator('.modal-content').locator('button:has-text("追加")').click();
		
		// 長い名前が表示されることを確認
		await expect(page.locator('h4:has-text("あいうえおかきくけこさしすせそたちつてと")')).toBeVisible();
		
		// ストック管理画面へ
		await page.click('h4:has-text("あいうえおかきくけこさしすせそたちつてと")');
		await page.waitForURL(/\/child\/.+/);
		
		// ストックを0にできることを確認
		const stockCard = page.locator('.stock-card').first();
		await expect(stockCard.locator('.stock-count')).toContainText('0');
		
		// ストックを最大まで増やす（例: 20まで）
		for (let i = 0; i < 5; i++) {
			await stockCard.locator('button:has-text("＋")').click();
			await page.waitForTimeout(100); // ネットワークリクエストを待つ
		}
		await expect(stockCard.locator('.stock-count')).toContainText('5');
		
		// 0まで減らす
		for (let i = 0; i < 5; i++) {
			await stockCard.locator('button:has-text("−")').click();
			await page.waitForTimeout(100); // ネットワークリクエストを待つ
		}
		await expect(stockCard.locator('.stock-count')).toContainText('0');
		
		// 0以下にはならないことを確認
		await expect(stockCard.locator('button:has-text("−")')).toBeDisabled();
	});
});