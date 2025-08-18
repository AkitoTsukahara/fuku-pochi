import { test, expect, devices } from '@playwright/test';

test.describe('Mobile Responsive E2E Tests', () => {
	
	test('should work properly on mobile devices', async ({ page }) => {
		// モバイル画面サイズを設定（iPhone 12サイズ）
		await page.setViewportSize({ width: 390, height: 844 });
		
		// 1. モバイル画面サイズでトップページにアクセス
		await page.goto('/');
		
		// ページが正しく読み込まれることを確認
		await expect(page).toHaveTitle(/ふくぽち/);
		
		// モバイル表示でのレイアウト確認
		await expect(page.locator('.welcome-section')).toBeVisible();
		
		// 2. グループ作成がモバイルで正しく動作する
		await page.fill('input[name="name"]', 'モバイルテストグループ');
		await page.click('button:has-text("グループを作成")');
		
		// グループ画面に遷移
		await page.waitForURL(/\/group\/.+/);
		await expect(page.locator('h2')).toContainText('モバイルテストグループ');
		
		// 3. モバイルで子どもを追加
		await page.click('text=お子さまを追加');
		
		// モーダルがモバイルで正しく表示されることを確認
		await expect(page.locator('.modal-content')).toBeVisible();
		
		await page.fill('input[name="name"]', 'モバイル太郎');
		await page.locator('.modal-content').locator('button:has-text("追加")').click();
		
		// 子どもが追加されたことを確認
		await expect(page.locator('h4:has-text("モバイル太郎")')).toBeVisible();
		
		// 4. モバイルでストック管理画面に移動
		await page.click('h4:has-text("モバイル太郎")');
		await page.waitForURL(/\/child\/.+/);
		
		// モバイル表示でのストック管理画面確認
		await expect(page.locator('.header__subtitle')).toContainText('モバイル太郎');
		
		// 5. モバイルでのストック操作
		const stockCard = page.locator('.stock-card').first();
		
		// モバイルでのボタンサイズが適切か確認（40px以上のタッチターゲット）
		const plusButton = stockCard.locator('button:has-text("＋")');
		const buttonBox = await plusButton.boundingBox();
		expect(buttonBox?.width).toBeGreaterThanOrEqual(40);
		expect(buttonBox?.height).toBeGreaterThanOrEqual(40);
		
		// ストック操作の実行
		await plusButton.click();
		await expect(stockCard.locator('.stock-count')).toContainText('1');
		
		await plusButton.click();
		await expect(stockCard.locator('.stock-count')).toContainText('2');
		
		// マイナスボタンのテスト
		await stockCard.locator('button:has-text("−")').click();
		await expect(stockCard.locator('.stock-count')).toContainText('1');
		
		// 6. モバイルでのナビゲーション確認
		await page.click('button:has-text("戻る")');
		await page.waitForURL(/\/group\/.+/);
		
		// グループ画面に戻ったことを確認
		await expect(page.locator('h4:has-text("モバイル太郎")')).toBeVisible();
	});

	test('should handle mobile gestures and touch interactions', async ({ page }) => {
		// モバイル画面サイズを設定
		await page.setViewportSize({ width: 390, height: 844 });
		
		// グループ作成
		await page.goto('/');
		await page.fill('input[name="name"]', 'タッチテストグループ');
		await page.click('button:has-text("グループを作成")');
		await page.waitForURL(/\/group\/.+/);
		
		// 子ども追加
		await page.click('text=お子さまを追加');
		await page.fill('input[name="name"]', 'タッチテスト子');
		await page.locator('.modal-content').locator('button:has-text("追加")').click();
		
		// ストック画面へ
		await page.click('h4:has-text("タッチテスト子")');
		await page.waitForURL(/\/child\/.+/);
		
		// タッチでのスクロールテスト（複数のストックカードがある場合）
		const stockCards = page.locator('.stock-card');
		const cardCount = await stockCards.count();
		
		if (cardCount > 3) {
			// ページをスクロールして下の方のカードにアクセス
			await page.locator('.stock-card').nth(cardCount - 1).scrollIntoViewIfNeeded();
			
			// 最後のストックカードが見えることを確認
			await expect(page.locator('.stock-card').nth(cardCount - 1)).toBeVisible();
		}
		
		// ボタンが適切にタップできることを確認
		const firstCard = page.locator('.stock-card').first();
		await firstCard.locator('button:has-text("＋")').click();
		await expect(firstCard.locator('.stock-count')).toContainText('1');
	});

	test('should work in landscape orientation on mobile', async ({ page }) => {
		// モバイルの横向きでテスト
		await page.setViewportSize({ width: 812, height: 375 }); // iPhone 横向きサイズ
		
		await page.goto('/');
		await page.fill('input[name="name"]', '横向きテストグループ');
		await page.click('button:has-text("グループを作成")');
		await page.waitForURL(/\/group\/.+/);
		
		// 横向きでのレイアウト確認
		await expect(page.locator('h2')).toContainText('横向きテストグループ');
		
		// 子ども追加
		await page.click('text=お子さまを追加');
		await page.fill('input[name="name"]', '横向き子');
		await page.locator('.modal-content').locator('button:has-text("追加")').click();
		
		// ストック画面で横向きレイアウト確認
		await page.click('h4:has-text("横向き子")');
		await page.waitForURL(/\/child\/.+/);
		
		// 横向きでもストック操作ができることを確認
		const stockCard = page.locator('.stock-card').first();
		await stockCard.locator('button:has-text("＋")').click();
		await expect(stockCard.locator('.stock-count')).toContainText('1');
		
		// Header が横向きでもコンパクトに表示されることを確認
		const headerTitle = page.locator('.header__title');
		await expect(headerTitle).toBeVisible();
	});

	test('should handle mobile-specific edge cases', async ({ page }) => {
		// モバイル画面サイズを設定
		await page.setViewportSize({ width: 390, height: 844 });
		
		// グループ作成
		await page.goto('/');
		await page.fill('input[name="name"]', 'エッジケースモバイル');
		await page.click('button:has-text("グループを作成")');
		await page.waitForURL(/\/group\/.+/);
		
		// 長い名前の子どもを追加（モバイルでの表示確認）
		await page.click('text=お子さまを追加');
		await page.fill('input[name="name"]', 'とてもながいなまえのこどもです');
		await page.locator('.modal-content').locator('button:has-text("追加")').click();
		
		// 長い名前がモバイルで適切に表示されることを確認
		await expect(page.locator('h4:has-text("とてもながいなまえのこどもです")')).toBeVisible();
		
		// ストック画面での長い名前表示
		await page.click('h4:has-text("とてもながいなまえのこどもです")');
		await page.waitForURL(/\/child\/.+/);
		
		await expect(page.locator('.header__subtitle')).toContainText('とてもながいなまえのこどもです');
		
		// モバイルでの連続タップテスト
		const stockCard = page.locator('.stock-card').first();
		const plusButton = stockCard.locator('button:has-text("＋")');
		
		// 連続で複数回タップ
		for (let i = 0; i < 3; i++) {
			await plusButton.click();
			await page.waitForTimeout(200); // ネットワークリクエストの完了を待つ
		}
		
		await expect(stockCard.locator('.stock-count')).toContainText('3');
		
		// 最小表示サイズでのテスト（非常に小さい画面）
		await page.setViewportSize({ width: 320, height: 568 }); // iPhone SE サイズ
		
		// 小さい画面でもボタンがタップできることを確認
		await expect(plusButton).toBeVisible();
		await plusButton.click();
		await expect(stockCard.locator('.stock-count')).toContainText('4');
	});

	test('should display properly on tablet size screens', async ({ page }) => {
		// タブレットサイズでのテスト
		await page.setViewportSize({ width: 768, height: 1024 }); // iPad サイズ
		
		await page.goto('/');
		await page.fill('input[name="name"]', 'タブレットテスト');
		await page.click('button:has-text("グループを作成")');
		await page.waitForURL(/\/group\/.+/);
		
		// タブレットサイズでの表示確認
		await expect(page.locator('h2')).toContainText('タブレットテスト');
		
		// 子ども追加
		await page.click('text=お子さまを追加');
		await page.fill('input[name="name"]', 'タブレット子');
		await page.locator('.modal-content').locator('button:has-text("追加")').click();
		
		// ストック画面でタブレット表示確認
		await page.click('h4:has-text("タブレット子")');
		await page.waitForURL(/\/child\/.+/);
		
		// タブレットサイズでストック操作
		const stockCard = page.locator('.stock-card').first();
		await stockCard.locator('button:has-text("＋")').click();
		await expect(stockCard.locator('.stock-count')).toContainText('1');
		
		// タブレットサイズでの要素サイズ確認
		const stockCards = page.locator('.stock-card');
		const cardCount = await stockCards.count();
		
		// タブレットでは複数のストックカードが横並びで表示されることを期待
		if (cardCount >= 2) {
			const firstCard = stockCards.nth(0);
			const secondCard = stockCards.nth(1);
			
			const firstCardBox = await firstCard.boundingBox();
			const secondCardBox = await secondCard.boundingBox();
			
			// 2つのカードが横に並んでいるか確認（Y座標が近い）
			if (firstCardBox && secondCardBox) {
				const yDifference = Math.abs(firstCardBox.y - secondCardBox.y);
				expect(yDifference).toBeLessThan(100); // 横並びなら Y座標の差は小さいはず
			}
		}
	});
});