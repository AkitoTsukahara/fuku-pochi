# データベースマイグレーション作成

## 概要
ふくぽちサービスのデータ構造を定義するマイグレーションファイルを作成する

## 作業内容
- [x] user_groups テーブルのマイグレーション作成
- [x] children テーブルのマイグレーション作成  
- [x] clothing_categories テーブルのマイグレーション作成
- [x] stock_items テーブルのマイグレーション作成
- [x] 外部キー制約とインデックスの設定
- [x] ユニークキー制約の設定

## 完了条件
- 全テーブルが正常に作成される
- 外部キー制約が適切に設定されている
- 必要なインデックスが作成されている
- マイグレーションのロールバックが可能

## 関連ファイル
- backend/database/migrations/2025_08_12_081642_create_user_groups_table.php
- backend/database/migrations/2025_08_12_081652_create_children_table.php
- backend/database/migrations/2025_08_12_081652_create_clothing_categories_table.php
- backend/database/migrations/2025_08_12_081652_create_stock_items_table.php

## 備考
- db-schema.mdの仕様通りに実装
- stock_items(child_id, clothing_category_id)の複合ユニークキー設定
- current_countは0以上の制約をDB層でも考慮