# タスク管理システム

このフォルダはClaude Codeでの開発タスクを管理するためのシステムです。

## フォルダ構成

```
agent-tasks/
├── todo/           # 未着手のタスク
├── in_progress/    # 進行中のタスク
├── completed/      # 完了したタスク
└── README.md       # この説明ファイル
```

## 使用方法

### 1. 新しいタスクの作成
`todo/` フォルダに新しいタスクファイルを作成します。

```bash
# 例：新しい機能の実装タスク
touch agent-tasks/todo/001-setup-project-structure.md
```

### 2. タスクファイルの命名規則
- `001-task-name.md` の形式で連番を付ける
- タスク名は英語のケバブケース（ハイフン区切り）で記述
- 拡張子は `.md` を使用

### 3. タスクの内容記述
各タスクファイルには以下の情報を記述：

```markdown
# タスク名

## 概要
タスクの概要説明

## 作業内容
- [ ] 作業項目1
- [ ] 作業項目2
- [ ] 作業項目3

## 完了条件
- 具体的な完了条件

## 関連ファイル
- 関連するファイルのパス

## 備考
その他の注意事項
```

### 4. タスクの進行管理
1. **開始時**: `todo/` から `in_progress/` に移動
2. **完了時**: `in_progress/` から `completed/` に移動

```bash
# タスク開始
mv agent-tasks/todo/001-setup-project-structure.md tasks/in_progress/

# タスク完了
mv agent-tasks/in_progress/001-setup-project-structure.md tasks/completed/
```

### 5. 進行状況の確認
```bash
# 各フォルダのタスク数を確認
ls agent-tasks/todo/ | wc -l
ls agent-tasks/in_progress/ | wc -l
ls agent-tasks/completed/ | wc -l
```

## 注意事項

- 同時に進行中のタスクは最大3つまでに制限することを推奨
- 完了したタスクは削除せず、`completed/` フォルダに保管
- タスクファイルには作業ログも記録することを推奨