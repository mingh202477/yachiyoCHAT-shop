-- PostgreSQL 18 商品购买系统初始化脚本

-- ==================== 1. 商品表 ====================
CREATE TABLE IF NOT EXISTS goods
(
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(50) DEFAULT '无名商品' NOT NULL,
    price       NUMERIC(10, 2) DEFAULT 999999 NOT NULL,
    good_type   VARCHAR(20) DEFAULT 'GIFT' NOT NULL,
    description TEXT DEFAULT '' NOT NULL,
    stock       INT DEFAULT 0 NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP
);

COMMENT ON TABLE goods IS '商品表';
COMMENT ON COLUMN goods.id IS '商品ID';
COMMENT ON COLUMN goods.name IS '商品名称';
COMMENT ON COLUMN goods.price IS '商品价格';
COMMENT ON COLUMN goods.good_type IS '商品类型';
COMMENT ON COLUMN goods.description IS '商品描述';
COMMENT ON COLUMN goods.stock IS '商品存货';
COMMENT ON COLUMN goods.created_at IS '创建时间';
COMMENT ON COLUMN goods.updated_at IS '更新时间';

CREATE INDEX IF NOT EXISTS idx_goods_good_type ON goods(good_type);
CREATE INDEX IF NOT EXISTS idx_goods_deleted_at ON goods(deleted_at);

-- ==================== 2. 用户背包表 ====================
CREATE TABLE IF NOT EXISTS user_backpack
(
    id          SERIAL PRIMARY KEY,
    user_id     BIGINT NOT NULL,
    good_id     INT NOT NULL REFERENCES goods(id) ON DELETE CASCADE,
    quantity    INT DEFAULT 1 NOT NULL,
    acquired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT user_backpack_unique UNIQUE(user_id, good_id)
);

COMMENT ON TABLE user_backpack IS '用户背包/物品表';
COMMENT ON COLUMN user_backpack.user_id IS '用户ID';
COMMENT ON COLUMN user_backpack.good_id IS '商品ID';
COMMENT ON COLUMN user_backpack.quantity IS '数量';
COMMENT ON COLUMN user_backpack.acquired_at IS '获得时间';

CREATE INDEX IF NOT EXISTS idx_user_backpack_user_id ON user_backpack(user_id);
CREATE INDEX IF NOT EXISTS idx_user_backpack_good_id ON user_backpack(good_id);

-- ==================== 3. 交易记录表 ====================
CREATE TABLE IF NOT EXISTS purchase_transactions
(
    id              SERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL,
    good_id         INT NOT NULL REFERENCES goods(id),
    quantity        INT DEFAULT 1 NOT NULL,
    unit_price      NUMERIC(10, 2) NOT NULL,
    total_amount    NUMERIC(10, 2) NOT NULL,
    transaction_id  VARCHAR(50) UNIQUE,
    trade_type      VARCHAR(20) DEFAULT 'BUY' NOT NULL,
    status          VARCHAR(20) DEFAULT 'SUCCESS' NOT NULL,
    external_ref    VARCHAR(100),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at     TIMESTAMP
);

COMMENT ON TABLE purchase_transactions IS '商品购买交易记录表';
COMMENT ON COLUMN purchase_transactions.user_id IS '购买用户ID';
COMMENT ON COLUMN purchase_transactions.good_id IS '购买商品ID';
COMMENT ON COLUMN purchase_transactions.quantity IS '购买数量';
COMMENT ON COLUMN purchase_transactions.unit_price IS '单价';
COMMENT ON COLUMN purchase_transactions.total_amount IS '总金额';
COMMENT ON COLUMN purchase_transactions.transaction_id IS '本地交易ID';
COMMENT ON COLUMN purchase_transactions.trade_type IS '交易类型';
COMMENT ON COLUMN purchase_transactions.status IS '交易状态';
COMMENT ON COLUMN purchase_transactions.external_ref IS '外部服务参考ID';
COMMENT ON COLUMN purchase_transactions.verified_at IS '验证时间';

CREATE INDEX IF NOT EXISTS idx_purchase_transactions_user_id ON purchase_transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_purchase_transactions_good_id ON purchase_transactions(good_id);
CREATE INDEX IF NOT EXISTS idx_purchase_transactions_created_at ON purchase_transactions(created_at);
CREATE INDEX IF NOT EXISTS idx_purchase_transactions_status ON purchase_transactions(status);

-- ==================== 4. 示例数据 ====================
INSERT INTO goods (name, price, good_type, description, stock) VALUES
('钻石礼包', 99.99, 'GIFT', '包含1000钻石', 1000),
('皮肤限定包', 49.99, 'SKIN', '限定版皮肤套装', 500),
('经验加速卡', 9.99, 'CARD', '24小时经验加速', 5000),
('金币礼包', 19.99, 'GIFT', '包含50000金币', 2000),
('VIP会员卡', 29.99, 'VIP', '30天VIP会员权限', 300)
ON CONFLICT DO NOTHING;

-- ==================== 5. 更新表所有者 ====================
ALTER TABLE goods OWNER TO postgres;
ALTER TABLE user_backpack OWNER TO postgres;
ALTER TABLE purchase_transactions OWNER TO postgres;
