# フロントエンド - グループ作成画面とURL共有機能

## 概要
家族グループの作成とURL共有によるアクセス共有機能のUIを実装する

## 作業内容
- [ ] グループ作成フォームの作成
- [ ] URL共有機能の実装
- [ ] QRコード生成機能の検討
- [ ] グループ情報表示画面の作成
- [ ] バリデーションエラー表示

## 完了条件
- 家族名を入力してグループが作成できる
- 作成後に共有用URLが表示される
- URLアクセスで既存グループに参加できる
- エラー時の適切なメッセージ表示
- スマートフォンでの操作性が良好

## 関連ファイル
- frontend/src/routes/+page.svelte
- frontend/src/routes/group/[token]/+page.svelte
- frontend/src/lib/components/GroupForm.svelte
- frontend/src/lib/components/ShareUrl.svelte

## 備考
- 初回訪問時はグループ作成画面を表示
- share_tokenによるルーティング設定
- レスポンシブデザインでの実装