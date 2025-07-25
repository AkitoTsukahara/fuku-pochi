# 衣類カテゴリAPI実装

## 概要
利用可能な衣類カテゴリ一覧を取得するAPI エンドポイントを実装する

## 作業内容
- [ ] GET /api/clothing-categories エンドポイントの実装
- [ ] sort_orderによる並び順制御
- [ ] レスポンス形式の統一
- [ ] キャッシュ対応の検討

## 完了条件
- 8種類の衣類カテゴリが正しい順序で取得できる
- アイコンパス情報が含まれる
- パフォーマンスが良好（キャッシュ利用）
- API仕様書通りのレスポンス形式

## 関連ファイル
- backend/app/Http/Controllers/Api/ClothingCategoryController.php
- backend/routes/api.php

## 備考
- 固定データのため更新系APIは不要
- フロントエンドでのアイコン表示に必要な情報を含める
- sort_orderでの昇順ソート