# ストック管理API実装

## 概要
子どもの衣類ストック数の取得・増減を行うAPI エンドポイントを実装する

## 作業内容
- [ ] GET /api/children/{id}/stock エンドポイントの実装
- [ ] POST /api/children/{id}/stock-increment エンドポイントの実装
- [ ] POST /api/children/{id}/stock-decrement エンドポイントの実装
- [ ] ストック数の0未満チェック実装
- [ ] 存在しないstock_itemの自動作成ロジック

## 完了条件
- 子どものストック一覧が衣類カテゴリ情報付きで取得できる
- ストック数の増減が正常に動作する
- ストック数が0未満にならない制御
- 初回アクセス時のstock_item自動作成
- 適切なエラーハンドリング

## 関連ファイル
- backend/app/Http/Controllers/Api/StockController.php
- backend/routes/api.php
- backend/app/Http/Requests/IncrementStockRequest.php
- backend/app/Http/Requests/DecrementStockRequest.php

## 備考
- レスポンスにはclothing_categoryの詳細情報も含める
- increment/decrementは1以上の整数のみ許可
- 同一子ども・同一カテゴリの重複作成を防ぐ