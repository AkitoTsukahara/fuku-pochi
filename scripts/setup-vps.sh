#!/bin/bash

# ==============================================================================
# FukuPochi VPS初回セットアップスクリプト
# Ubuntu 22.04 LTS対応
# ==============================================================================

set -euo pipefail

# 色付きログ用の関数
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# ==============================================================================
# 設定変数
# ==============================================================================

PROJECT_NAME="fuku-pochi"
REPO_URL="https://github.com/AkitoTsukahara/fuku-pochi.git"
DEPLOY_USER="deploy"
DEPLOY_DIR="/var/www/${PROJECT_NAME}"
DOMAIN=""

# ==============================================================================
# 引数チェック
# ==============================================================================

if [ $# -eq 0 ]; then
    log_error "Usage: $0 <domain-name>"
    log_info "Example: $0 example.com"
    exit 1
fi

DOMAIN="$1"
log_info "ドメイン設定: ${DOMAIN}"

# ==============================================================================
# 前提条件チェック
# ==============================================================================

log_info "前提条件をチェック中..."

# root権限チェック
if [[ $EUID -ne 0 ]]; then
   log_error "このスクリプトはroot権限で実行してください"
   exit 1
fi

# Ubuntu バージョンチェック
if ! grep -q "Ubuntu" /etc/os-release; then
    log_warning "このスクリプトはUbuntu用に最適化されています"
fi

# ==============================================================================
# システムアップデート
# ==============================================================================

log_info "システムアップデート実行中..."
apt update && apt upgrade -y
log_success "システムアップデート完了"

# ==============================================================================
# 基本パッケージインストール
# ==============================================================================

log_info "基本パッケージインストール中..."
apt install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release \
    ufw \
    fail2ban \
    htop \
    vim \
    nano \
    tree

log_success "基本パッケージインストール完了"

# ==============================================================================
# Docker & Docker Compose インストール
# ==============================================================================

log_info "Docker & Docker Composeインストール中..."

# Docker公式リポジトリ追加
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

echo \
  "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null

# Dockerインストール
apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Dockerサービス開始・自動起動設定
systemctl start docker
systemctl enable docker

log_success "Docker & Docker Composeインストール完了"

# ==============================================================================
# デプロイユーザー作成
# ==============================================================================

log_info "デプロイユーザー作成中..."

# デプロイユーザーが存在しない場合のみ作成
if ! id -u ${DEPLOY_USER} > /dev/null 2>&1; then
    useradd -m -s /bin/bash ${DEPLOY_USER}
    usermod -aG docker ${DEPLOY_USER}
    
    # sudo権限付与
    echo "${DEPLOY_USER} ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/${DEPLOY_USER}
    
    log_success "デプロイユーザー '${DEPLOY_USER}' 作成完了"
else
    log_warning "デプロイユーザー '${DEPLOY_USER}' は既に存在します"
fi

# ==============================================================================
# SSH設定（セキュリティ強化）
# ==============================================================================

log_info "SSH設定を強化中..."

# SSH設定バックアップ
cp /etc/ssh/sshd_config /etc/ssh/sshd_config.backup

# SSH設定更新
cat >> /etc/ssh/sshd_config << EOF

# FukuPochi セキュリティ設定
Protocol 2
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
AuthorizedKeysFile .ssh/authorized_keys
PermitEmptyPasswords no
ChallengeResponseAuthentication no
UsePAM yes
X11Forwarding no
PrintMotd no
ClientAliveInterval 600
ClientAliveCountMax 3
MaxAuthTries 3
MaxSessions 10
EOF

systemctl reload sshd
log_success "SSH設定強化完了"

# ==============================================================================
# ファイアウォール設定
# ==============================================================================

log_info "ファイアウォール設定中..."

# UFW初期化
ufw --force reset

# デフォルトポリシー
ufw default deny incoming
ufw default allow outgoing

# SSH許可
ufw allow ssh

# HTTP/HTTPS許可
ufw allow 80/tcp
ufw allow 443/tcp

# UFW有効化
ufw --force enable

log_success "ファイアウォール設定完了"

# ==============================================================================
# Fail2ban設定
# ==============================================================================

log_info "Fail2ban設定中..."

cat > /etc/fail2ban/jail.local << EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
backend = systemd

[sshd]
enabled = true
port = ssh
logpath = %(sshd_log)s
backend = %(sshd_backend)s

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
logpath = /var/log/nginx/error.log
maxretry = 2

[nginx-noscript]
enabled = true
filter = nginx-noscript
logpath = /var/log/nginx/access.log
maxretry = 6
EOF

systemctl enable fail2ban
systemctl start fail2ban

log_success "Fail2ban設定完了"

# ==============================================================================
# プロジェクトディレクトリ作成
# ==============================================================================

log_info "プロジェクトディレクトリ作成中..."

mkdir -p ${DEPLOY_DIR}
chown -R ${DEPLOY_USER}:${DEPLOY_USER} ${DEPLOY_DIR}

log_success "プロジェクトディレクトリ作成完了: ${DEPLOY_DIR}"

# ==============================================================================
# Certbot (Let's Encrypt) インストール
# ==============================================================================

log_info "Certbot (Let's Encrypt) インストール中..."

snap install core; snap refresh core
snap install --classic certbot
ln -sf /snap/bin/certbot /usr/bin/certbot

# Certbot用ディレクトリ作成
mkdir -p /var/www/certbot
chown -R ${DEPLOY_USER}:${DEPLOY_USER} /var/www/certbot

log_success "Certbotインストール完了"

# ==============================================================================
# ログディレクトリ作成
# ==============================================================================

log_info "ログディレクトリ作成中..."

mkdir -p /var/log/{nginx,php-fpm,php,supervisor,mysql,redis}
chown -R ${DEPLOY_USER}:${DEPLOY_USER} /var/log/{php-fpm,php,supervisor}

log_success "ログディレクトリ作成完了"

# ==============================================================================
# スワップファイル作成（1GB RAM環境用）
# ==============================================================================

log_info "スワップファイル作成中..."

if [ ! -f /swapfile ]; then
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' | tee -a /etc/fstab
    
    # スワップ使用頻度を調整（VPS最適化）
    echo 'vm.swappiness=10' >> /etc/sysctl.conf
    
    log_success "スワップファイル作成完了 (2GB)"
else
    log_warning "スワップファイルは既に存在します"
fi

# ==============================================================================
# システム最適化
# ==============================================================================

log_info "システム最適化設定中..."

# ファイル記述子制限緩和
cat >> /etc/security/limits.conf << EOF
* soft nofile 65535
* hard nofile 65535
EOF

# カーネルパラメータ最適化
cat >> /etc/sysctl.conf << EOF
# FukuPochi 最適化設定
net.core.somaxconn = 1024
net.ipv4.tcp_max_syn_backlog = 1024
net.ipv4.ip_local_port_range = 1024 65535
vm.overcommit_memory = 1
EOF

sysctl -p

log_success "システム最適化完了"

# ==============================================================================
# 自動更新設定
# ==============================================================================

log_info "自動セキュリティ更新設定中..."

apt install -y unattended-upgrades

cat > /etc/apt/apt.conf.d/20auto-upgrades << EOF
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Download-Upgradeable-Packages "1";
APT::Periodic::AutocleanInterval "7";
APT::Periodic::Unattended-Upgrade "1";
EOF

log_success "自動セキュリティ更新設定完了"

# ==============================================================================
# 最終確認とサマリー
# ==============================================================================

log_success "==================================="
log_success "VPS初回セットアップ完了！"
log_success "==================================="

echo ""
log_info "設定サマリー:"
echo "  - ドメイン: ${DOMAIN}"
echo "  - デプロイユーザー: ${DEPLOY_USER}"
echo "  - プロジェクトディレクトリ: ${DEPLOY_DIR}"
echo "  - Docker: $(docker --version)"
echo "  - Docker Compose: $(docker compose version)"

echo ""
log_info "次のステップ:"
echo "  1. SSH公開鍵を ${DEPLOY_USER} ユーザーに設定"
echo "  2. プロジェクトをクローン: sudo -u ${DEPLOY_USER} git clone ${REPO_URL} ${DEPLOY_DIR}"
echo "  3. 環境変数設定: .env.production を作成"
echo "  4. デプロイスクリプト実行: ./scripts/deploy.sh"
echo "  5. SSL証明書取得: certbot --nginx -d ${DOMAIN}"

echo ""
log_warning "重要: rootでのSSHログインは無効化されています"
log_warning "今後は '${DEPLOY_USER}' ユーザーでSSHログインしてください"

echo ""
log_success "セットアップ完了！サーバーを再起動することをお勧めします。"