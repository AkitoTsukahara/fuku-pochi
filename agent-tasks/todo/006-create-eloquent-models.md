# Laravel 12.x Eloquentモデル作成

## 概要
データベーステーブルと対応するEloquentモデルクラスを作成し、リレーションシップを定義する

## 作業内容
- [ ] UserGroupモデルの作成とリレーション定義
- [ ] Childrenモデルの作成とリレーション定義
- [ ] ClothingCategoryモデルの作成
- [ ] StockItemモデルの作成とリレーション定義
- [ ] 各モデルのfillable属性設定
- [ ] バリデーションルールの設定

## 完了条件
- 全モデルクラスが作成され、適切なリレーションが定義されている
- モデル間の関連性が正しく動作する
- 必要な属性がfillableに設定されている
- 基本的なCRUD操作が可能

## 関連ファイル
- backend/app/Models/UserGroup.php
- backend/app/Models/Children.php
- backend/app/Models/ClothingCategory.php
- backend/app/Models/StockItem.php

## 備考
- UserGroup hasMany Children
- Children belongsTo UserGroup, hasMany StockItems
- StockItem belongsTo Children, belongsTo ClothingCategory
- share_tokenの自動生成ロジックも検討