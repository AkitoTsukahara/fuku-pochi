# 衣類カテゴリ初期データ投入用シーダー作成

## 概要
アプリケーションで使用する8種類の衣類カテゴリの初期データを投入するシーダーを作成する

## 作業内容
- [ ] ClothingCategorySeederクラスの作成
- [ ] 8種類の衣類カテゴリデータの定義
- [ ] アイコンパスとソート順の設定
- [ ] DatabaseSeederへの登録
- [ ] シーダー実行の動作確認

## 完了条件
- php artisan db:seed でカテゴリデータが投入される
- 全8種類のカテゴリが正しい順序で登録される
- アイコンパスが適切に設定される
- 重複実行時のエラーハンドリングが動作する

## 関連ファイル
- backend/database/seeders/ClothingCategorySeeder.php
- backend/database/seeders/DatabaseSeeder.php

## 備考
- 固定カテゴリ: Tシャツ、ズボン、靴下、ハンカチ、肌着、ぼうし、水着セット、ビニール袋
- アイコンパス: /icons/[category].svg 形式
- sort_order: 1-8の連番