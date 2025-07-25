# SvelteKitのAPI通信ライブラリとストア設定

## 概要
バックエンドAPIとの通信を行うクライアントライブラリと状態管理用ストアを構築する

## 作業内容
- [ ] API通信用のベースクライアント作成
- [ ] 各エンドポイント用のAPIクライアント関数作成
- [ ] Svelte storesによる状態管理設定
- [ ] エラーハンドリングの共通化
- [ ] ローディング状態の管理

## 完了条件
- 全APIエンドポイントとの通信が可能
- 状態管理が適切に動作する
- エラー状態とローディング状態が管理されている
- TypeScriptの型定義が完備されている

## 関連ファイル
- frontend/src/lib/api/client.ts
- frontend/src/lib/api/groups.ts
- frontend/src/lib/api/children.ts
- frontend/src/lib/api/stock.ts
- frontend/src/lib/stores/group.ts
- frontend/src/lib/stores/children.ts
- frontend/src/lib/stores/stock.ts

## 備考
- fetchベースのHTTPクライアント
- リアクティブな状態更新
- エラーメッセージの日本語化