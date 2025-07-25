# データベースマイグレーション作成

## 概要
ふくぽちサービスのデータ構造を定義するマイグレーションファイルを作成する

## 作業内容
- [ ] user_groups テーブルのマイグレーション作成
- [ ] children テーブルのマイグレーション作成  
- [ ] clothing_categories テーブルのマイグレーション作成
- [ ] stock_items テーブルのマイグレーション作成
- [ ] 外部キー制約とインデックスの設定
- [ ] ユニークキー制約の設定

## 完了条件
- 全テーブルが正常に作成される
- 外部キー制約が適切に設定されている
- 必要なインデックスが作成されている
- マイグレーションのロールバックが可能

## 関連ファイル
- backend/database/migrations/xxxx_create_user_groups_table.php
- backend/database/migrations/xxxx_create_children_table.php
- backend/database/migrations/xxxx_create_clothing_categories_table.php
- backend/database/migrations/xxxx_create_stock_items_table.php

## 備考
- db-schema.mdの仕様通りに実装
- stock_items(child_id, clothing_category_id)の複合ユニークキー設定
- current_countは0以上の制約をDB層でも考慮