update jg_styles_cust sc set read_num = (
	select sum(read_num) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust
)


update jg_styles_cust sc set ac_zp = (
	select count(1) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust and zp.check = 'y'
)

update jg_styles_cust sc set has_top = (
	select count(1) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust and zp.is_top = 1
)



update jg_styles_cust sc set read_num = (
	select sum(read_num) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust
), ac_zp = (
	select count(1) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust and zp.check = 'y'
), has_top = (
	select count(1) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust and zp.is_top = 1
)


update jg_styles_cust sc set 
read_num = ( select sum(read_num) from jg_zp zp where zp.style = sc.style and zp.cust = sc.cust ), 
ac_zp = ( select count(1) from jg_zp zp where zp.style = sc.style and zp.cust = sc.cust and zp.check = 'y' ), 
has_top = ( select count(1) from jg_zp zp where zp.style = sc.style and zp.cust = sc.cust and zp.is_top = 1 ),
c_time= (select max(createtime) from jg_zp zp where zp.style = sc.style and zp.cust = sc.cust and zp.check = 'y')


------------------------------
v1版本+
------------------------------
-- 加入最新时间
ALTER TABLE `jg_styles_cust` 
ADD COLUMN `c_time` int(10) UNSIGNED NULL COMMENT '最新时间' AFTER `has_top`;

-- 加入通知
ALTER TABLE `jg_notify` 
MODIFY COLUMN `type` enum('1','2','3','4','5') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '3' COMMENT '通知类型:1=作品,2=订单,3=通知,4=星级,5=预约' AFTER `style`;
-- 删除图片缓存
update jg_zp set fximage = null , qrimage = null;
-- 核心代理
ALTER TABLE `jg_cust` 
ADD COLUMN `is_agent_vip` enum('y','n') NOT NULL DEFAULT 'n' COMMENT '是否v代理:y=是,n=否' AFTER `is_teacher`;


------------------------------
v2版本+
------------------------------
-- 添加热图
ALTER TABLE `jg_styles_cust` 
ADD COLUMN `hotimage` varchar(255) NULL COMMENT '最高热度作品' AFTER `c_time`;

-- 设置热门图片
update jg_styles_cust sc set hotimage = (select covorimage from jg_zp zp where sc.cust = zp.cust and sc.style = zp.style ORDER BY read_num desc limit 1)

-- 设置摄影类目
ALTER TABLE `jg_order` 
ADD COLUMN `style` int(10) UNSIGNED NULL COMMENT '摄影类目' AFTER `endedtime`;