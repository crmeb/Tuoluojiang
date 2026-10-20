/*M!999999\- enable the sandbox mode */
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `eb_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_admin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `account` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户账号',
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户密码',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户头像',
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户姓名',
  `phone` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `job` int unsigned NOT NULL DEFAULT '0' COMMENT '职位ID',
  `is_admin` tinyint NOT NULL DEFAULT '0' COMMENT '是否为超级管理员',
  `roles` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '角色权限',
  `uni_online` tinyint(1) NOT NULL DEFAULT '0' COMMENT '移动端登录状态',
  `client_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '连接通道ID',
  `scan_key` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '扫码登录参数',
  `last_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '访问ip',
  `login_count` int NOT NULL DEFAULT '0' COMMENT '登陆次数',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：0、锁定；1、正常；',
  `is_init` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否为初始密码',
  `language` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
  `mark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `work_member_id` int NOT NULL DEFAULT '0' COMMENT '企业微信成功id',
  `mcp_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'MCP工具调用唯一值',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `e_sign` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否认证电子签',
  `e_userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电子签用户ID',
  `e_openid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电子签openid',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE,
  UNIQUE KEY `admin_mcp_key_unique` (`mcp_key`) USING BTREE,
  KEY `eb_admin_account_index` (`account`) USING BTREE,
  KEY `eb_admin_name_index` (`name`) USING BTREE,
  KEY `eb_admin_phone_index` (`phone`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='管理员员工表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_admin_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_admin_info` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `letter` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '#' COMMENT '姓氏首字母',
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '城市',
  `area` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '区域',
  `card_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证号',
  `province` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '省份',
  `birthday` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '员工生日',
  `nation` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '员工种族',
  `politic` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '政治面貌',
  `education` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学历',
  `education_image` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学历证书',
  `acad` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学位',
  `acad_image` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学位证书',
  `native` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '籍贯',
  `address` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '居住地',
  `sex` tinyint NOT NULL DEFAULT '0' COMMENT '性别: 0、未知；1、男；2、女；3、其他；',
  `age` tinyint unsigned DEFAULT NULL COMMENT '员工年龄',
  `marriage` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '婚姻状况:0、未婚；1、已婚；',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '员工状态:0、未入职；1、正式;2、使用;3、实习;4、离职；',
  `work_years` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '工作经验（年）',
  `spare_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '紧急联系人',
  `spare_tel` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '紧急联系电话',
  `email` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `social_num` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '社保账户',
  `fund_num` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '公积金账户',
  `bank_num` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '银行卡账户',
  `bank_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行',
  `graduate_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '毕业院校',
  `graduate_date` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '毕业时间',
  `interview_date` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '面试时间',
  `interview_position` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '面试职位',
  `is_part` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否兼职',
  `photo` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '员工照片',
  `card_front` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证正面',
  `card_both` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证背面',
  `work_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '入职时间',
  `trial_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '试用时间',
  `formal_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '转正时间',
  `treaty_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '合同到期时间',
  `quit_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '离职时间',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='管理员员工信息表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_agreement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_agreement` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `ident` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '协议标识',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '协议标题',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '协议内容',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='协议表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_approve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_approve` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `card_id` bigint unsigned NOT NULL COMMENT '创建用户名片ID',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审批名称',
  `icon` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审批图标',
  `color` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审批图标颜色',
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '审批说明',
  `types` int unsigned NOT NULL DEFAULT '0' COMMENT '审批类型：见枚举；',
  `examine` int unsigned NOT NULL DEFAULT '1' COMMENT '是否需要审核',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单配置详情',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：0、关闭；1、开启；',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_approve_card_id_foreign` (`card_id`) USING BTREE,
  KEY `eb_enterprise_approve_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='审批流';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_approve_apply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_approve_apply` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `card_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户名片ID',
  `approve_id` bigint unsigned NOT NULL COMMENT '审批流ID',
  `node_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '当前节点ID',
  `examine` int unsigned NOT NULL DEFAULT '1' COMMENT '是否需要审批：0、无须审批；1、需要审批；',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '申请状态：-1、撤回；0、待审批；1、已通过；2、已拒绝；',
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '说明',
  `number` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '编号',
  `crud_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联实体ID',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '实体数据ID',
  `apply_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联审批ID',
  `is_recall` int unsigned NOT NULL DEFAULT '0' COMMENT '是否为撤销审批',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_approve_apply_approve_id_foreign` (`approve_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='审批流申请';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_approve_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_approve_content` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `card_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户名片ID',
  `approve_id` bigint unsigned NOT NULL COMMENT '审批流ID',
  `apply_id` bigint unsigned NOT NULL COMMENT '审批申请ID',
  `title` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表单名称',
  `info` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表单提示',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单默认值',
  `required` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否必选',
  `types` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表单类型',
  `symbol` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段标识',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单详情',
  `props` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '限制条件',
  `options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单配置信息',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单配置信息',
  `uniqued` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表单唯一值',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_approve_content_approve_id_foreign` (`approve_id`) USING BTREE,
  KEY `eb_enterprise_approve_content_apply_id_foreign` (`apply_id`) USING BTREE,
  KEY `eb_approve_content_symbol_index` (`symbol`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='审批流申请内容';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_approve_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_approve_form` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `card_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户名片ID',
  `approve_id` bigint unsigned NOT NULL COMMENT '审批流ID',
  `title` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表单名称',
  `info` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表单提示',
  `value` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表单默认值',
  `required` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否必选',
  `types` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表单类型',
  `symbol` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段标识',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单详情',
  `props` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '限制条件',
  `options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单配置信息',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单配置信息',
  `uniqued` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表单唯一值',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_approve_form_approve_id_foreign` (`approve_id`) USING BTREE,
  KEY `eb_approve_form_symbol_index` (`symbol`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='审批流表单';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_approve_holiday_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_approve_holiday_type` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '假期类型',
  `new_employee_limit` tinyint NOT NULL DEFAULT '0' COMMENT '新员工请假限制：0、不限制；1、限制；',
  `new_employee_limit_month` tinyint NOT NULL DEFAULT '1' COMMENT '新员工请假月时限制',
  `duration_type` tinyint NOT NULL DEFAULT '0' COMMENT '请假时长类型：0、天；1、小时；',
  `duration_calc_type` tinyint NOT NULL DEFAULT '1' COMMENT '时长计算类型：0、自然日；1、工作日；',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='假期类型';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_approve_process`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_approve_process` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建企业ID',
  `card_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户名片ID',
  `approve_id` bigint unsigned NOT NULL COMMENT '审批流ID',
  `level` int unsigned NOT NULL DEFAULT '1' COMMENT '流程级别',
  `groups` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '分组ID',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '节点名称',
  `types` tinyint unsigned NOT NULL COMMENT '节点类型：0、申请人；1、审批人；2、抄送人；3、条件；4、路由；',
  `uniqued` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '节点唯一值',
  `settype` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '审核人类型：1、指定成员；2、指定部门主管；7、连续多部门；5、申请人自己；4、申请人自选；(0、无此条件)',
  `director_order` tinyint NOT NULL DEFAULT '-1' COMMENT '指定层级顺序：0、从上至下；1、从下至上；(-1、无此条件)',
  `director_level` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '指定主管层级/指定终点层级：1-10；(0、无此条件)',
  `no_hander` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '当前部门无负责人时：1、上级部门负责人审批；2、为空时跳过；(0、无此条件)',
  `dep_head` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '指定部门负责人',
  `self_select` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否允许自选抄送人',
  `select_range` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '可选范围：1、不限范围；2、指定成员；(0、无此条件)',
  `user_list` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '指定的成员列表',
  `select_mode` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '选人方式：1、单选；2、多选；(0、无此条件)',
  `examine_mode` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '多人审批方式：1、或签；2、会签；3、依次审批；(0、无此条件)',
  `priority` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '条件优先级',
  `parent` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '节点父级唯一值',
  `is_child` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否存在子节点',
  `is_condition` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否存在条件',
  `condition_list` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '条件详情',
  `is_initial` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否为初始数据',
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '数据详情',
  `pass_ratio` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '通过比例(%) 0=关闭（使用原逻辑）',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_approve_process_approve_id_foreign` (`approve_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='审批流流程';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_approve_reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_approve_reply` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `card_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户名片ID',
  `apply_id` bigint unsigned NOT NULL COMMENT '审批申请ID',
  `content` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '回复内容',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_approve_reply_apply_id_foreign` (`apply_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='审批流回复';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_approve_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_approve_rule` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `card_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户名片ID',
  `approve_id` bigint unsigned NOT NULL COMMENT '审批流ID',
  `range` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '可见范围',
  `abnormal` int unsigned NOT NULL DEFAULT '0' COMMENT '异常处理：0、自动同意；指定处理人ID；',
  `auto` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自动审批：0、首个节点处理，其他自动同意；1、连续审批自动同意；2、每个节点都需审批；',
  `edit` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '修改权限：0、员工不可修改固定人员；1、不可删除固定抄送人；',
  `recall` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '异常处理：1、审批通过后允许撤销；',
  `is_transfer` int unsigned NOT NULL DEFAULT '0' COMMENT '是否可转审',
  `is_sign` int unsigned NOT NULL DEFAULT '0' COMMENT '是否可加签',
  `refuse` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT '被拒绝后：0、返回初始，所有人重新审批；1、跳过已通过层级；',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_approve_rule_approve_id_foreign` (`approve_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='审批流规则';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_approve_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_approve_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `card_id` int unsigned NOT NULL DEFAULT '0' COMMENT '相关用户名片ID',
  `approve_id` bigint unsigned NOT NULL COMMENT '审批流ID',
  `apply_id` bigint unsigned NOT NULL COMMENT '审批申请ID',
  `node_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审核节点ID(唯一值)',
  `level` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '级别',
  `sort` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '审批顺序',
  `verify` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '操作状态：0、自动；1、手动；',
  `status` int NOT NULL DEFAULT '0' COMMENT '审批状态：-1、无需审批；0、待审批；1、已通过；2、已拒绝；',
  `is_sign` int unsigned NOT NULL DEFAULT '0' COMMENT '是否为加签',
  `is_transfer` int unsigned NOT NULL DEFAULT '0' COMMENT '是否为转审：0、正常节点；1、已转审；2、被转审；',
  `parent` int unsigned NOT NULL DEFAULT '0' COMMENT '转审人ID',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '类型：1、审核人；2、抄送人；',
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '人员详情',
  `process_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '流程节点详情',
  `content` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '人员说明',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_approve_user_approve_id_foreign` (`approve_id`) USING BTREE,
  KEY `eb_enterprise_approve_user_apply_id_foreign` (`apply_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='审批流用户';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_assess`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_assess` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `period` tinyint NOT NULL DEFAULT '0' COMMENT '周期:1=周;2=月;3=年',
  `planid` bigint unsigned NOT NULL DEFAULT '0' COMMENT '计划ID',
  `frame_id` int NOT NULL DEFAULT '0' COMMENT '组织架构ID',
  `number` int NOT NULL DEFAULT '0' COMMENT '考核批次ID',
  `check_uid` int NOT NULL DEFAULT '0' COMMENT '考核用户信息表ID',
  `test_uid` int NOT NULL DEFAULT '0' COMMENT '被考核用户信息表ID',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '考核开始时间',
  `make_time` timestamp NULL DEFAULT NULL COMMENT '目标制定时间结束时间',
  `make_status` tinyint NOT NULL DEFAULT '0' COMMENT '目标制定状态：0、未制定；1、已启用；2、草稿。',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '考核结束时间',
  `test_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '自评状态：0、未评价；1、已评价；2、草稿；',
  `check_end` timestamp NULL DEFAULT NULL COMMENT '上级评价结束时间',
  `check_status` tinyint NOT NULL DEFAULT '0' COMMENT '上级评价状态：0、未评价；1、已评价；2、草稿。',
  `verify_time` timestamp NULL DEFAULT NULL COMMENT '审核结束时间',
  `verify_status` tinyint NOT NULL DEFAULT '0' COMMENT '审核状态：0、未审核；1、已审核；',
  `score` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '考核得分',
  `total` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '最高分',
  `grade` int NOT NULL DEFAULT '0' COMMENT '考核等级',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '考核状态：0、目标制定；1、自评期；2、上级评价；3、审核期；4、结束；',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '评分方式：0、加权评分；1、加和评分',
  `intact` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '完整性：1、是；0、否',
  `is_show` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否启用：0、未启用；1、已启用；',
  `self_reply` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自评',
  `reply` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上级评价',
  `hide_reply` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上级评价(仅上级可见)',
  `delete` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_assess_self_reply_index` (`self_reply`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='绩效考核';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_assess_compute_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_assess_compute_rule` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `scheme_id` int NOT NULL DEFAULT '0' COMMENT '方案(enterprise_performance_scheme主键)id',
  `row` int NOT NULL DEFAULT '2' COMMENT '行数',
  `col` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A' COMMENT '列数',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_assess_compute_rule_scheme_id_index` (`scheme_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment='绩效考核计算规则';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_assess_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_assess_plan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `create_time` tinyint(1) NOT NULL DEFAULT '1' COMMENT '星期:1-7/或者几号1-31',
  `create_month` int NOT NULL DEFAULT '0' COMMENT '月',
  `assess_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '被考核人类型：0=人员添加,1=部门添加',
  `test_frame` json DEFAULT NULL COMMENT '考核部门ID列表',
  `test_user` json DEFAULT NULL COMMENT '考核人员ID列表',
  `period` tinyint NOT NULL DEFAULT '0' COMMENT '周期:1=周;2=月;3=年;5=季度;4=半年',
  `make_type` enum('before','after','start') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'before' COMMENT '目标制定时间类型：考核开始前、考核开始后',
  `make_day` int NOT NULL DEFAULT '0' COMMENT '目标制定天数',
  `eval_type` enum('before','after','start') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'before' COMMENT '上级评价时间类型：考核结束前、考核结束后',
  `eval_day` int NOT NULL DEFAULT '0' COMMENT '上级评价天数',
  `verify_type` enum('before','after') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'after' COMMENT '审核时间类型：评价结束前、评价结束后',
  `verify_day` int NOT NULL DEFAULT '0' COMMENT '绩效审核天数',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0=禁用;1=开启',
  `uniqued` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '任务唯一值',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_assess_plan_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='绩效考核计划';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_assess_reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_assess_reply` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `assessid` bigint unsigned NOT NULL COMMENT '考核ID',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '企业用户ID',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容',
  `is_own` tinyint NOT NULL DEFAULT '0' COMMENT '自身可见：0、否；1、是',
  `types` tinyint NOT NULL DEFAULT '0' COMMENT '类型：0、评价；1、申诉',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '申诉状态：0、评价；1、已处理；2、已拒绝；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='绩效考核评价';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_assess_scheme`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_assess_scheme` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `period` tinyint NOT NULL DEFAULT '0' COMMENT '周期:1=周;2=月;3=年',
  `create_type` enum('time','monday','tuesday','wednesday','thursday','friday','saturday','sunday') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'time' COMMENT '生成绩效日期类型',
  `create_month` int NOT NULL DEFAULT '0' COMMENT '生成绩效月份',
  `create_day` int NOT NULL DEFAULT '0' COMMENT '生成绩效日期',
  `create_time` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '生成绩效时间',
  `own_appraise_period` enum('year','nextyear','month','nextmonth','monday','tuesday','wednesday','thursday','friday','saturday','sunday') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'year' COMMENT '自评结束时间类型',
  `own_appraise_month` int NOT NULL DEFAULT '0' COMMENT '自评结束月份',
  `own_appraise_day` int NOT NULL DEFAULT '0' COMMENT '自评结束日期',
  `own_appraise_time` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自评结束时间',
  `leader_appraise_period` enum('year','nextyear','month','nextmonth','monday','tuesday','wednesday','thursday','friday','saturday','sunday') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'year' COMMENT '上级评分结束时间类型',
  `leader_appraise_month` int NOT NULL DEFAULT '0' COMMENT '上级评分结束月份',
  `leader_appraise_day` int NOT NULL DEFAULT '0' COMMENT '上级评分结束日期',
  `leader_appraise_time` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上级评分结束时间',
  `user_id` int NOT NULL COMMENT '企业成员ID(admin主键ID)',
  `user_count` int NOT NULL DEFAULT '0' COMMENT '被考核人数',
  `file_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件标识',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0=禁用;1=开启',
  `delete` timestamp NULL DEFAULT NULL COMMENT '是否删除',
  `other` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '其他数据',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_assess_scheme_entid_foreign` (`entid`) USING BTREE,
  KEY `eb_enterprise_assess_scheme_user_id_index` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='绩效考核方案';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_assess_score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_assess_score` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户关联企业表(admin主键)ID',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '等级名称',
  `min` int unsigned NOT NULL DEFAULT '0' COMMENT '分数最小值',
  `max` int unsigned NOT NULL DEFAULT '0' COMMENT '分数最大值',
  `level` tinyint NOT NULL DEFAULT '0' COMMENT '级别',
  `mark` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '说明',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_assess_score_entid_index` (`entid`) USING BTREE,
  KEY `eb_enterprise_assess_score_user_id_index` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='绩效考核分数';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_assess_space`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_assess_space` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `assessid` int NOT NULL DEFAULT '0' COMMENT '考核列表ID',
  `targetid` int NOT NULL DEFAULT '0' COMMENT '考核模板ID',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '维度名称',
  `ratio` int NOT NULL DEFAULT '0' COMMENT '维度占比',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_assess_space_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='绩效考核维度';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_assess_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_assess_target` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `spaceid` int NOT NULL DEFAULT '0' COMMENT '维度ID',
  `ratio` int NOT NULL DEFAULT '0' COMMENT '权重占比',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '指标名称',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '指标内容',
  `info` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '评分等级',
  `finish_info` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '完成情况',
  `finish_ratio` int NOT NULL DEFAULT '0' COMMENT '完成百分比',
  `check_info` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '上级评价',
  `max` int NOT NULL DEFAULT '0' COMMENT '最高得分',
  `score` int NOT NULL DEFAULT '0' COMMENT '评价得分',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='绩效考核指标';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_assess_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_assess_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `scheme_id` bigint unsigned NOT NULL,
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户关联企业表(admin主键)ID',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_assess_user_scheme_id_index` (`scheme_id`) USING BTREE,
  KEY `eb_enterprise_assess_user_user_id_index` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='绩效考核用户';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_assess_user_score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_assess_user_score` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `assessid` int NOT NULL DEFAULT '0' COMMENT '考核记录ID',
  `userid` int NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `check_uid` int unsigned NOT NULL DEFAULT '0' COMMENT '考核人ID',
  `test_uid` int unsigned NOT NULL DEFAULT '0' COMMENT '被考核人ID',
  `score` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '考核得分',
  `total` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '最高分',
  `grade` int unsigned DEFAULT '0' COMMENT '考核等级',
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '变更说明',
  `mark` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注信息',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '操作类型：0、评分；1、删除绩效；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='绩效考核用户得分';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_assist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_assist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '辅助表自增id',
  `main_id` int NOT NULL DEFAULT '1' COMMENT '主表ID',
  `aux_id` int NOT NULL DEFAULT '1' COMMENT '副表ID',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '类型,可用其他表名区分',
  `other` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '其他数据',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='辅助表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_apply_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_apply_record` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `uid` int NOT NULL COMMENT '申请人',
  `apply_type` tinyint NOT NULL DEFAULT '0' COMMENT '审批申请类型：1：请假；2：补卡；3：加班；4：外出；5：出差；',
  `type_unique` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '类型/异常标识',
  `date_type` tinyint NOT NULL DEFAULT '0' COMMENT '日期类型：1：工作日；2：休息日；3：节假日；',
  `time_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '工时类型：day：天；hour：小时；minute：分钟；',
  `calc_type` tinyint NOT NULL DEFAULT '0' COMMENT '核算方式：1：调休；2：加班费；',
  `work_hours` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '加班时长',
  `apply_id` int NOT NULL COMMENT '申请记录ID',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '开始时间',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '结束时间',
  `others` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '其他标识',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤申请记录';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_arrange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_arrange` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int NOT NULL COMMENT '考勤组ID',
  `uid` int NOT NULL COMMENT '业务员ID',
  `date` timestamp NOT NULL COMMENT '考勤时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤排班';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_arrange_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_arrange_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `arrange_id` bigint NOT NULL COMMENT '排班ID',
  `group_id` int NOT NULL COMMENT '考勤组ID',
  `uid` int NOT NULL COMMENT '业务员ID',
  `shift_id` int NOT NULL COMMENT '班次ID',
  `date` timestamp NOT NULL COMMENT '排班日期',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤排班记录';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_clock_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_clock_record` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `frame_id` int NOT NULL COMMENT '部门ID',
  `group_id` int NOT NULL COMMENT '考勤组ID',
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '考勤组名称',
  `shift_id` int NOT NULL COMMENT '考勤班次ID',
  `shift_data` varchar(511) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '班次数据',
  `address` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '打卡地址',
  `lat` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '纬度',
  `lng` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '经度',
  `clock_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '打卡方式：0、位置；1、Wifi',
  `mac` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '打卡Mac地址',
  `remark` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '图片',
  `uid` int NOT NULL COMMENT '考勤人员ID',
  `is_external` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '外勤打卡:0、考勤打卡；1、外勤打卡；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤打卡记录';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '考勤组名称',
  `type` int unsigned NOT NULL DEFAULT '0' COMMENT '考勤类型:0、人员；1、部门；',
  `address` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '详细地址',
  `lat` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '纬度',
  `lng` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '经度',
  `effective_range` int NOT NULL COMMENT '有效范围',
  `location_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '考勤地点名称',
  `repair_allowed` tinyint unsigned NOT NULL COMMENT '允许补卡',
  `repair_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '补卡类型:1、缺卡;2、迟到;3、严重迟到;4、早退；',
  `is_limit_time` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '补卡时间限制:0、不限制；1、限制；',
  `limit_time` int NOT NULL DEFAULT '0' COMMENT '补卡时间',
  `is_limit_number` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '补卡次数限制:0、不限制；1、限制；',
  `limit_number` int NOT NULL DEFAULT '0' COMMENT '补卡次数',
  `is_photo` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '拍照打卡:0、不限制；1、限制；',
  `is_map` int unsigned NOT NULL DEFAULT '1' COMMENT '地图打卡:0、不限制；1、限制；',
  `is_wifi` int unsigned NOT NULL DEFAULT '0' COMMENT 'Wifi打卡:0、不限制；1、限制；',
  `is_external` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '外勤打卡:0、不限制；1、限制；',
  `is_external_note` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '外勤打卡备注:0、不限制；1、限制；',
  `is_external_photo` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '外勤打卡拍照:0、不限制；1、限制；',
  `uid` int NOT NULL COMMENT '业务员ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤组';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_group_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_group_member` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int NOT NULL COMMENT '考勤组ID',
  `entid` int NOT NULL DEFAULT '1' COMMENT '企业ID',
  `member` int NOT NULL COMMENT '考勤类型ID',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '考勤成员类型:0、考勤人员；1、无需考勤人员；2、考勤组负责人；3、考勤部门；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤组成员';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_group_shift`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_group_shift` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int unsigned NOT NULL COMMENT '考勤组ID',
  `shift_id` int unsigned NOT NULL COMMENT '班次表ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤组班次';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_handle_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_handle_record` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `statistics_id` bigint NOT NULL COMMENT '统计ID',
  `shift_number` tinyint NOT NULL COMMENT '班次编号',
  `before_status` tinyint NOT NULL DEFAULT '0' COMMENT '修改前状态',
  `before_location_status` tinyint NOT NULL DEFAULT '0' COMMENT '修改前外勤状态',
  `after_status` tinyint NOT NULL DEFAULT '0' COMMENT '修改后状态',
  `after_location_status` tinyint NOT NULL DEFAULT '0' COMMENT '修改后外勤状态',
  `result` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '打卡结果',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `source` tinyint NOT NULL DEFAULT '0' COMMENT '来源：0、手动修改；1、补卡申请；',
  `uid` int NOT NULL COMMENT '操作人',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤处理记录';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_remind`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_remind` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `shift_id` int NOT NULL COMMENT '班次ID',
  `shift_num` tinyint NOT NULL DEFAULT '0' COMMENT '打卡班次数量',
  `one_shift_time` timestamp NULL DEFAULT NULL COMMENT '一班次上班时间',
  `one_shift_remind` timestamp NULL DEFAULT NULL COMMENT '一班次上班提醒',
  `one_shift_remind_push` tinyint NOT NULL DEFAULT '0' COMMENT '一班次上班是否推送',
  `one_shift_remind_short` timestamp NULL DEFAULT NULL COMMENT '一班次上班缺卡提醒',
  `two_shift_time` timestamp NULL DEFAULT NULL COMMENT '一班次下班时间',
  `two_shift_remind` timestamp NULL DEFAULT NULL COMMENT '一班次下班提醒',
  `two_shift_remind_push` tinyint NOT NULL DEFAULT '0' COMMENT '一班次下班是否推送',
  `two_shift_remind_short` timestamp NULL DEFAULT NULL COMMENT '一班次下班缺卡提醒',
  `three_shift_time` timestamp NULL DEFAULT NULL COMMENT '二班次上班时间',
  `three_shift_remind` timestamp NULL DEFAULT NULL COMMENT '二班次上班提醒',
  `three_shift_remind_push` tinyint NOT NULL DEFAULT '0' COMMENT '二班次上班是否推送',
  `three_shift_remind_short` timestamp NULL DEFAULT NULL COMMENT '二班次上班缺卡提醒',
  `four_shift_time` timestamp NULL DEFAULT NULL COMMENT '二班次下班时间',
  `four_shift_remind` timestamp NULL DEFAULT NULL COMMENT '二班次下班提醒',
  `four_shift_remind_push` tinyint NOT NULL DEFAULT '0' COMMENT '二班次下班是否推送',
  `four_shift_remind_short` timestamp NULL DEFAULT NULL COMMENT '二班次下班缺卡提醒',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤打卡提醒表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_shift`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_shift` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '班次名称',
  `number` tinyint NOT NULL DEFAULT '1' COMMENT '上下班次数 0、休息；1、1次上下班；2、2次上下班；',
  `rest_time` tinyint NOT NULL DEFAULT '0' COMMENT '中途休息：1、开启；0、关闭；',
  `rest_start` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '休息开始时间',
  `rest_end` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '休息结束时间',
  `rest_start_after` tinyint NOT NULL DEFAULT '0' COMMENT '休息开始规则 0、当日；1、次日；',
  `rest_end_after` tinyint NOT NULL DEFAULT '0' COMMENT '休息结束规则 0、当日；1、次日；',
  `overtime` int NOT NULL DEFAULT '0' COMMENT '加班起算时间',
  `work_time` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '工作时长',
  `color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '颜色标识',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `uid` int NOT NULL COMMENT '业务员ID',
  `types` tinyint(1) NOT NULL DEFAULT '0' COMMENT '班次类型:0、自定义;1、默认班次;2、休息;',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='班次';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_shift_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_shift_rule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `shift_id` int NOT NULL COMMENT '班次表ID',
  `number` tinyint NOT NULL DEFAULT '1' COMMENT '次数 1、1次上下班；2、2次上下班；',
  `first_day_after` tinyint NOT NULL DEFAULT '0' COMMENT '上班当日次数 0、当日；1、次日；',
  `second_day_after` tinyint NOT NULL DEFAULT '0' COMMENT '下班当日次数 0、当日；1、次日；',
  `work_hours` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上班时间',
  `late` int NOT NULL DEFAULT '0' COMMENT '迟到',
  `extreme_late` int NOT NULL DEFAULT '0' COMMENT '严重迟到',
  `late_lack_card` int NOT NULL DEFAULT '0' COMMENT '晚到缺卡',
  `early_card` int NOT NULL DEFAULT '0' COMMENT '提前打卡',
  `off_hours` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '下班时间',
  `early_leave` int NOT NULL DEFAULT '0' COMMENT '早退',
  `early_lack_card` int NOT NULL DEFAULT '0' COMMENT '提前缺卡',
  `delay_card` int NOT NULL DEFAULT '0' COMMENT '延后打卡',
  `free_clock` tinyint NOT NULL DEFAULT '0' COMMENT '下班可免打卡',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='班次规则';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_short_remind`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_short_remind` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `shift_id` int NOT NULL COMMENT '班次ID',
  `uid` int NOT NULL COMMENT '员工ID',
  `short_type` tinyint NOT NULL DEFAULT '0' COMMENT '提醒类型：0、上班；1、下班；',
  `work_time` timestamp NULL DEFAULT NULL COMMENT '上班时间',
  `remind_time` timestamp NULL DEFAULT NULL COMMENT '推送时间',
  `is_push` tinyint NOT NULL DEFAULT '0' COMMENT '是否推送',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='打卡提醒';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_statistics` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `uid` int NOT NULL COMMENT '考勤人员ID',
  `frame_id` int NOT NULL COMMENT '部门ID',
  `group_id` int NOT NULL COMMENT '考勤组ID',
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '考勤组名称',
  `shift_id` int NOT NULL COMMENT '考勤班次ID',
  `shift_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '班次数据',
  `one_shift_time` timestamp NULL DEFAULT NULL COMMENT '一班次上班打卡时间',
  `one_shift_is_after` tinyint NOT NULL COMMENT '当日次数：0、当日；1、次日；',
  `one_shift_status` tinyint NOT NULL DEFAULT '0' COMMENT '打卡状态：0、无需打卡；1、正常；2、迟到；3、严重迟到；4、早退；5、缺卡；',
  `one_shift_location_status` tinyint NOT NULL DEFAULT '0' COMMENT '地点状态:0、正常；1、外勤；2、地点异常；',
  `one_shift_record_id` bigint NOT NULL COMMENT '打卡记录ID',
  `two_shift_time` timestamp NULL DEFAULT NULL COMMENT '二班次下班打卡时间',
  `two_shift_is_after` tinyint NOT NULL COMMENT '当日次数：0、当日；1、次日；',
  `two_shift_status` tinyint NOT NULL DEFAULT '0' COMMENT '打卡状态：0、无需打卡；1、正常；2、迟到；3、严重迟到；4、早退；5、缺卡；',
  `two_shift_location_status` tinyint NOT NULL DEFAULT '0' COMMENT '地点状态:0、正常；1、外勤；2、地点异常；',
  `two_shift_record_id` bigint NOT NULL COMMENT '打卡记录ID',
  `three_shift_time` timestamp NULL DEFAULT NULL COMMENT '三班次上班打卡时间',
  `three_shift_is_after` tinyint NOT NULL COMMENT '当日次数：0、当日；1、次日；',
  `three_shift_status` tinyint NOT NULL DEFAULT '0' COMMENT '打卡状态：0、无需打卡；1、正常；2、迟到；3、严重迟到；4、早退；5、缺卡；',
  `three_shift_location_status` tinyint NOT NULL DEFAULT '0' COMMENT '地点状态:0、正常；1、外勤；2、地点异常；',
  `three_shift_record_id` bigint NOT NULL COMMENT '打卡记录ID',
  `four_shift_time` timestamp NULL DEFAULT NULL COMMENT '四班次下班打卡时间',
  `four_shift_is_after` tinyint NOT NULL COMMENT '当日次数：0、当日；1、次日；',
  `four_shift_status` tinyint NOT NULL DEFAULT '0' COMMENT '打卡状态：0、无需打卡；1、正常；2、迟到；3、严重迟到；4、早退；5、缺卡；',
  `four_shift_location_status` tinyint NOT NULL DEFAULT '0' COMMENT '地点状态:0、正常；1、外勤；2、地点异常；',
  `four_shift_record_id` bigint NOT NULL COMMENT '打卡记录ID',
  `required_work_hours` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '应出勤工时',
  `actual_work_hours` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '实际出勤工时',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `statistics_date` date GENERATED ALWAYS AS (date(`created_at`)) STORED COMMENT '统计日期',
  `active_flag` tinyint GENERATED ALWAYS AS (if(`deleted_at` is null,1,null)) STORED COMMENT '有效标记',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uniq_attendance_statistics_uid_date_active` (`uid`,`statistics_date`,`active_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤统计';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_statistics_leave`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_statistics_leave` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `statistics_id` bigint NOT NULL COMMENT '考勤记录ID',
  `apply_record_id` int NOT NULL COMMENT '申请记录ID',
  `uid` int NOT NULL COMMENT '考勤人员ID',
  `type_unique` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '请假类型',
  `leave_duration` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '请假工时',
  `holiday_type_id` int NOT NULL DEFAULT '0' COMMENT '假期类型ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='请假统计';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_whitelist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_whitelist` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `uid` int NOT NULL COMMENT '业务员ID',
  `type` int unsigned NOT NULL DEFAULT '0' COMMENT '人员类型:0、人员；1、管理员；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤白名单';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_attendance_wifi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_attendance_wifi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `group_id` int unsigned NOT NULL DEFAULT '0' COMMENT '考勤组ID',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'wifi名称',
  `mac` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'wifi地址',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_attendance_wifi_mac_index` (`mac`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤wifi';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_knowledge_base`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_knowledge_base` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '知识库名称',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `icon` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '知识库图标',
  `color` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '知识库图标颜色',
  `space_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '空间类型:1内部,2外部',
  `visibility` json DEFAULT NULL COMMENT '可见范围',
  `embedding_model_id` int unsigned NOT NULL DEFAULT '0' COMMENT '向量模型配置ID',
  `chunk_mode` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '分块模式:1自动,2手动',
  `chunk_size` int unsigned NOT NULL DEFAULT '800' COMMENT '分块长度',
  `chunk_overlap` int unsigned NOT NULL DEFAULT '100' COMMENT '重叠长度',
  `doc_count` int unsigned NOT NULL DEFAULT '0' COMMENT '文档数',
  `chunk_count` int unsigned NOT NULL DEFAULT '0' COMMENT '分块数',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态:0禁用,1启用',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_uid` (`uid`) USING BTREE,
  KEY `idx_embedding_model` (`embedding_model_id`) USING BTREE,
  KEY `idx_space_status` (`space_type`,`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_knowledge_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_knowledge_document` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `kb_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '知识库ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '上传人',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文档标题',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '原文件名',
  `file_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件类型',
  `file_size` int unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '存储路径',
  `attach_id` int unsigned NOT NULL DEFAULT '0' COMMENT '附件ID',
  `content_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '解析文本',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态:0待处理,1处理中,2完成,3失败',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `chunk_count` int unsigned NOT NULL DEFAULT '0' COMMENT '分块数',
  `processed_at` timestamp NULL DEFAULT NULL COMMENT '处理完成时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_uid` (`uid`) USING BTREE,
  KEY `idx_file_type` (`file_type`) USING BTREE,
  KEY `idx_attach_id` (`attach_id`) USING BTREE,
  KEY `idx_kb_status` (`kb_id`,`status`) USING BTREE,
  FULLTEXT KEY `idx_content_text` (`content_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库文档表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_knowledge_chunk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_knowledge_chunk` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `kb_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '知识库ID',
  `doc_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '文档ID',
  `chunk_index` int unsigned NOT NULL DEFAULT '0' COMMENT '分块序号',
  `source_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '来源:1自动,2手动',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分块文本',
  `content_html` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '分块富文本(展示)',
  `content_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '内容HASH',
  `embedding` json DEFAULT NULL COMMENT '向量',
  `embedding_model_id` int unsigned NOT NULL DEFAULT '0' COMMENT '向量模型ID',
  `embedding_dimension` smallint unsigned NOT NULL DEFAULT '0' COMMENT '向量维度',
  `metadata` json DEFAULT NULL COMMENT '元数据',
  `image_urls` json DEFAULT NULL COMMENT '分块图片链接数组',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态:0待向量化,1完成,2失败',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '向量化失败原因',
  `enabled` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '启用:0关闭,1开启',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_kb_status` (`kb_id`,`status`) USING BTREE,
  KEY `idx_doc_index` (`doc_id`,`chunk_index`) USING BTREE,
  KEY `idx_doc_enabled_source` (`doc_id`,`enabled`,`source_type`) USING BTREE,
  KEY `idx_content_hash` (`content_hash`) USING BTREE,
  KEY `idx_embedding_model` (`embedding_model_id`) USING BTREE,
  FULLTEXT KEY `idx_content_fulltext` (`content`) /*!50100 WITH PARSER `ngram` */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库分块表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_knowledge_question_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_knowledge_question_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `kb_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '知识库ID',
  `app_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT 'Chat应用ID',
  `chat_record_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '会话记录UUID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '问题',
  `hit_chunks` json DEFAULT NULL COMMENT '命中分块',
  `answer_summary` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '回答摘要',
  `source` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '来源',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_kb_id` (`kb_id`) USING BTREE,
  KEY `idx_uid` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库问答日志表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_knowledge_eval_case`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_knowledge_eval_case` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户问题',
  `golden_chunk_ids` json DEFAULT NULL COMMENT '标准答案所在切片ID',
  `answer_points` json DEFAULT NULL COMMENT '标准回答要点',
  `kb_ids` json DEFAULT NULL COMMENT '限定检索的知识库ID',
  `space_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '空间类型:1内部,2外部',
  `tags` json DEFAULT NULL COMMENT '场景标签',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态:0草稿,1确认,2停用',
  `source` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual' COMMENT '来源:manual/question_log',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_space_status` (`space_type`,`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库评测用例表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_knowledge_eval_run`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_knowledge_eval_run` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '运行名称',
  `note` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '运行说明',
  `feature_flags` json DEFAULT NULL COMMENT '特性开关与检索配置快照',
  `metrics` json DEFAULT NULL COMMENT 'recall@3/5、mrr、miss_rate、明细',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态:0pending,1running,2done,3failed',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '失败摘要',
  `created_by` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_created_by` (`created_by`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库评测运行表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_bill_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_bill_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路径',
  `level` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '级别',
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `cate_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类编号',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级ID',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '类型:0,支出;1,收入',
  `contact_id` int unsigned NOT NULL DEFAULT '0' COMMENT '联系人ID',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_bill_category_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='账单分类表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_bill_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_bill_list` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建成员ID',
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '创建成员ID',
  `cate_id` int NOT NULL DEFAULT '0' COMMENT '财务流水分类ID',
  `num` decimal(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '变动金额',
  `edit_time` timestamp NULL DEFAULT NULL COMMENT '变动时间',
  `types` tinyint NOT NULL DEFAULT '0' COMMENT '变动类型:1=收入,0=支出',
  `type_id` int unsigned NOT NULL DEFAULT '0' COMMENT '支付方式ID',
  `pay_type` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付方式名称',
  `mark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注信息',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联ID',
  `order_id` int unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `link_cate` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联类型',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_bill_list_entid_foreign` (`entid`) USING BTREE,
  KEY `eb_enterprise_bill_list_user_id_index` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='财务流水';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_calendar_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_calendar_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `day` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '日期',
  `is_rest` tinyint NOT NULL DEFAULT '1' COMMENT '是否休息 0、上班；1、休息；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日历配置';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '分类自增id',
  `pid` int NOT NULL DEFAULT '0' COMMENT '父级ID',
  `cate_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路径(存储分类层级关系，如: 1/3/5表示三级分类)',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序(数字越小越靠前)',
  `pic` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标',
  `is_show` tinyint NOT NULL DEFAULT '1' COMMENT '是否显示(1=显示，0=隐藏)',
  `level` int NOT NULL DEFAULT '0' COMMENT '等级(1=一级分类，2=二级分类，以此类推)',
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类类型: contract-合同分类, invoice-发票分类, bill-收支分类, storage-存储分类, position-职位分类, assess-考核分类, notepad-备忘录分类, memorial-纪念日分类, message-消息分类等',
  `keyword` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标记词(搜索关键词)',
  `entid` int NOT NULL DEFAULT '0' COMMENT '平台编号；0、总后台；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `m_type` (`type`,`entid`) USING BTREE,
  KEY `show_cate` (`id`,`is_show`) USING BTREE,
  KEY `type_cate` (`id`,`type`,`level`,`is_show`) USING BTREE,
  KEY `eb_category_pid_index` (`pid`) USING BTREE,
  KEY `eb_category_type_index` (`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分类表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_chat_app_auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_chat_app_auth` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `app_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_chat_app_auth_user_id_app_id_index` (`user_id`,`app_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI应用成员关联表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_chat_mcp_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_chat_mcp_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '服务名称',
  `info` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '简介',
  `config_json` json DEFAULT NULL COMMENT 'MCP单条server配置JSON(内置含module)',
  `is_default` tinyint NOT NULL DEFAULT '0' COMMENT '是否为默认服务：0=否，1=是',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_is_default` (`status`,`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MCP服务配置表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_chat_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_chat_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `pic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标',
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '简介',
  `edit_ids` json DEFAULT NULL COMMENT '编辑权限成员ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `status` int unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `app_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '应用类型：0=内部，1=外部',
  `share_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外部分享密钥',
  `share_enabled` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否开启外部分享',
  `share_expire_at` timestamp NULL DEFAULT NULL COMMENT '分享过期时间',
  `auth_ids` json DEFAULT NULL COMMENT '可使用成员ID',
  `use_limit` int unsigned NOT NULL DEFAULT '0' COMMENT '使用频次',
  `guest_use_limit` int unsigned NOT NULL DEFAULT '0' COMMENT '外部应用当日访客消息总上限(0=不限)',
  `guest_json` json DEFAULT NULL COMMENT '外部访客配置',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `models_id` int unsigned NOT NULL DEFAULT '0' COMMENT '模型ID',
  `enable_thinking` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否启用模型深度思考:0关闭,1开启',
  `count_number` int unsigned NOT NULL DEFAULT '0' COMMENT '对话轮数',
  `tables` json DEFAULT NULL COMMENT '数据库表名',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '数据库内容',
  `tooltip_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '提示词',
  `prologue_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '开场白',
  `prologue_list` json DEFAULT NULL COMMENT '开场白问题',
  `json` json DEFAULT NULL COMMENT '高级设置',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `source_type` json DEFAULT NULL COMMENT '插件类型：1=数据库，2=MCP，4=知识库',
  `keyword` json DEFAULT NULL COMMENT '关键字',
  `data_arrange_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '整理数据规格',
  `mcp_json` json DEFAULT NULL COMMENT 'MCP配置JSON',
  `mcp_service_ids` json DEFAULT NULL COMMENT '关联MCP服务ID数组',
  `knowledge_ids` json DEFAULT NULL COMMENT '关联知识库ID数组',
  `draft_json` json DEFAULT NULL COMMENT '未发布草稿，正式会话不读取',
  `agent_json` json DEFAULT NULL COMMENT 'Agent编排调参(max_steps/write_confirmation等)',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_chat_applications_uid_name_index` (`uid`,`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI应用表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_chat_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_chat_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `guest_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访客凭证',
  `session_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0=登录用户 1=访客',
  `chat_application_id` int unsigned NOT NULL DEFAULT '0' COMMENT '应用id',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `top_up` timestamp NULL DEFAULT NULL COMMENT '置顶时间',
  `is_show` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_chat_history_user_id_index` (`user_id`) USING BTREE,
  KEY `eb_chat_history_chat_application_id_index` (`chat_application_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI会话历史表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_chat_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_chat_models` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名称',
  `provider` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型驱动',
  `config_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '配置类型:1预设,2自定义,3一号通',
  `base_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型基础地址',
  `is_full_url` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否完整URL:0拼接path,1不拼接',
  `chat_path` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/chat/completions' COMMENT '聊天接口路径',
  `embedding_path` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/embeddings' COMMENT '向量接口路径',
  `model_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型ID',
  `model_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '模型类型:1会话,2向量',
  `api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'API Key',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态:0禁用,1启用',
  `enable_thinking` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '思考模式:0关闭,1开启,2跟随默认',
  `json` json DEFAULT NULL COMMENT '高级设置',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_chat_models_uid_index` (`uid`) USING BTREE,
  KEY `eb_chat_models_provider_index` (`provider`) USING BTREE,
  KEY `eb_chat_models_config_type_index` (`config_type`) USING BTREE,
  KEY `eb_chat_models_model_type_index` (`model_type`) USING BTREE,
  KEY `eb_chat_models_status_index` (`status`) USING BTREE,
  KEY `eb_chat_models_uid_name_index` (`uid`,`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI模型配置表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_chat_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_chat_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chat_record_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'uuid',
  `chat_history_id` int unsigned NOT NULL DEFAULT '0' COMMENT '记录对话历史主键id',
  `vote_status` int unsigned NOT NULL DEFAULT '0' COMMENT '赞扬状态',
  `problem_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '提问内容',
  `answer_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '回答内容',
  `sql_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'sql内容',
  `prompt_tokens` int NOT NULL DEFAULT '0' COMMENT '问题tokens数',
  `completion_tokens` int NOT NULL DEFAULT '0' COMMENT '回答tokens数',
  `tokens` int NOT NULL DEFAULT '0' COMMENT '总tokens数',
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '详情',
  `run_time` int NOT NULL DEFAULT '0' COMMENT '运行时间记录',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `uid` int NOT NULL DEFAULT '0' COMMENT '用户id',
  `is_show` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否展示1=展示',
  `chat_applications_id` int NOT NULL DEFAULT '0' COMMENT 'AI应用id',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_chat_record_chat_record_uuid_index` (`chat_record_uuid`) USING BTREE,
  KEY `eb_chat_record_chat_history_id_index` (`chat_history_id`) USING BTREE,
  KEY `eb_chat_record_vote_status_index` (`vote_status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI会话内容表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_bill`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_bill` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `eid` int unsigned NOT NULL DEFAULT '0' COMMENT '客户ID',
  `cid` int NOT NULL DEFAULT '0' COMMENT '合同ID',
  `cate_id` int NOT NULL DEFAULT '0' COMMENT '续费类型ID',
  `bill_cate_id` int NOT NULL DEFAULT '0' COMMENT '续费类型ID',
  `bill_types` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '类型:0,支出;1,收入',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户ID',
  `invoice_id` int NOT NULL DEFAULT '0' COMMENT '发票ID',
  `num` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `mark` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `types` tinyint NOT NULL DEFAULT '0' COMMENT '类型：0，合同；1，续费；',
  `type_id` int unsigned NOT NULL DEFAULT '0' COMMENT '支付方式ID',
  `pay_type` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付方式名称',
  `date` timestamp NULL DEFAULT NULL COMMENT '收款日期',
  `end_date` timestamp NULL DEFAULT NULL COMMENT '续费结束日期',
  `bill_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '付款单号',
  `apply_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联申请审批ID',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '类型：0，待审核；1，已通过；2，未通过',
  `fail_msg` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户付款流水表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_bill_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_bill_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `bill_list_id` int NOT NULL DEFAULT '0' COMMENT '付款流水ID',
  `uid` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '操作类型',
  `operation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '日志内容',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_client_bill_list_log_entid_index` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户付款流水日志表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_contract_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_contract_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级ID',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路径',
  `level` tinyint NOT NULL DEFAULT '1' COMMENT '级别',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `bill_cate_id` int NOT NULL DEFAULT '0' COMMENT '账目分类ID',
  `bill_cate_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '账目分类路径',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `cate_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类编号',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='合同订单分类关联财务分类表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_file` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '附件ID',
  `eid` int unsigned NOT NULL DEFAULT '0' COMMENT '客户ID',
  `cid` int unsigned NOT NULL DEFAULT '0' COMMENT '合同ID',
  `fid` int unsigned NOT NULL DEFAULT '0' COMMENT '跟进记录ID',
  `vid` int unsigned NOT NULL DEFAULT '0' COMMENT '发票申请ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '上传用户ID',
  `name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件名称',
  `real_name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件原始名称',
  `att_dir` char(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件路径',
  `thumb_dir` char(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件压缩路径',
  `att_size` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件大小',
  `att_type` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件类型',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '分后台ID',
  `up_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '上传方式：1、本地；2、七牛云；3、OSS；4、COS。',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户附件表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_follow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_follow` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `eid` int NOT NULL DEFAULT '0' COMMENT '客户ID',
  `link_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联业务',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '说明内容',
  `types` tinyint NOT NULL DEFAULT '0' COMMENT '类型：0，说明；1，提醒；',
  `time` timestamp NULL DEFAULT NULL COMMENT '提醒时间',
  `uniqued` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '定时任务唯一值',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态：0、待处理；1、放弃；2、已完成；',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `follow_version` int NOT NULL DEFAULT '0' COMMENT '跟进版本',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户跟进记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_invoice` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `unique` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '唯一值',
  `serial_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发票流水号',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '业务员ID',
  `eid` int NOT NULL DEFAULT '0' COMMENT '客户ID',
  `cid` int NOT NULL DEFAULT '0' COMMENT '合同ID',
  `category_id` int NOT NULL DEFAULT '0' COMMENT '发票类目ID',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '发票名称',
  `num` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '发票编号',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '合同金额',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '发票金额',
  `types` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发票类型',
  `title` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发票抬头',
  `ident` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '纳税人识别号',
  `bank` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行',
  `account` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户账号',
  `address` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开票地址',
  `tel` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电话',
  `collect_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮寄联系人',
  `collect_tel` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮寄联系电话',
  `collect_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮寄方式',
  `collect_email` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮寄邮箱',
  `mail_address` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮寄地址',
  `invoice_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '开票方式',
  `invoice_address` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '开票地址',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '发票状态 -1：开票撤回；0：待开票；1：已开票；2:已拒绝；3：申请冲红；4:同意冲红；5：拒绝冲红；6：冲红撤回；',
  `invalid` tinyint NOT NULL DEFAULT '0' COMMENT '冲红状态: 0，默认；-1，撤回；1，待审核；2，审核通过；3，审核未通过',
  `bill_date` date DEFAULT NULL COMMENT '开票日期',
  `real_date` date DEFAULT NULL COMMENT '实际开票日期',
  `mark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '备注内容',
  `remark` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开票备注',
  `card_remark` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '业务员备注',
  `finance_remark` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '财务备注',
  `creator` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建人ID',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联审批ID',
  `revoke_id` int unsigned NOT NULL DEFAULT '0' COMMENT '撤销申请ID',
  `link_bill` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联付款单ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_client_invoice_unique_index` (`unique`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户发票表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_invoice_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_invoice_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '类目名称',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='发票类目';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_invoice_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_invoice_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entid` int NOT NULL COMMENT '企业ID',
  `invoice_id` int NOT NULL COMMENT '发票ID',
  `uid` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `type` tinyint NOT NULL COMMENT '操作类型',
  `operation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '日志内容',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='发票日志';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_label`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_label` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标签名称',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级ID',
  `group_type` tinyint NOT NULL DEFAULT '0' COMMENT '标签组类型:0=手动标签,1=系统标签',
  `is_work` tinyint NOT NULL DEFAULT '0' COMMENT '0=本地标签，1=企业微信标签',
  `work_group_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业微信标签分组ID',
  `work_tag_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '企业微信标签ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_client_label_entid_foreign` (`entid`) USING BTREE,
  KEY `idx_client_label_pid_sort_id` (`pid`,`sort`,`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户标签';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_list` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建用户ID',
  `cid` int DEFAULT '0' COMMENT '分类ID',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `client_no` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户编号',
  `name` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户名称',
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户邮箱',
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户邮箱',
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户来源',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户地址',
  `detail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '地址详情',
  `follow` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否关注',
  `up_follow` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '上级是否关注',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '成交状态：0、未成交；1、已成交；',
  `creator` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '创建人ID',
  `mark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '备注信息',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '修改时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户列表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_remind`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_remind` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `eid` int NOT NULL DEFAULT '0' COMMENT '客户ID',
  `cid` int NOT NULL DEFAULT '0' COMMENT '合同ID',
  `cate_id` int NOT NULL DEFAULT '0' COMMENT '续费类型ID',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `bill_id` int NOT NULL DEFAULT '0' COMMENT '付款单ID',
  `num` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `mark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '备注',
  `time` timestamp NULL DEFAULT NULL COMMENT '提醒时间',
  `this_period` timestamp NULL DEFAULT NULL COMMENT '本期时间',
  `next_period` timestamp NULL DEFAULT NULL COMMENT '下期时间',
  `uniqued` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '定时任务唯一值',
  `rate` int unsigned NOT NULL DEFAULT '0' COMMENT '重复频率',
  `period` tinyint NOT NULL DEFAULT '0' COMMENT '重复周期：0、天；1、周；2、月；3、年',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '类型：0、回款；1、续费；',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态：0、正常；1、放弃；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '修改时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户提醒';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_client_subscribe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_client_subscribe` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `uid` int NOT NULL COMMENT '用户ID',
  `eid` int NOT NULL COMMENT '关联客户ID',
  `types` tinyint NOT NULL DEFAULT '0' COMMENT '类型：0、客户；1、线索；2、商机；',
  `subscribe_status` tinyint NOT NULL DEFAULT '0' COMMENT '关注状态：0、取消关注；1、已关注；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_client_subscribe_entid_index` (`entid`) USING BTREE,
  KEY `eb_enterprise_client_subscribe_uid_index` (`uid`) USING BTREE,
  KEY `eb_enterprise_client_subscribe_eid_index` (`eid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户关注表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_contract`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_contract` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` int NOT NULL DEFAULT '0' COMMENT '业务员ID',
  `eid` int NOT NULL DEFAULT '0' COMMENT '客户ID',
  `oid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '商机编号',
  `creator_uid` int NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `contract_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '订单名称',
  `contract_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '订单编号',
  `outbound_warehouse_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '出库仓库ID',
  `contract_price` decimal(10,2) DEFAULT '0.00' COMMENT '合同金额(元)',
  `received` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '回款金额',
  `surplus` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '尾款金额',
  `contract_followed` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1' COMMENT '是否关注',
  `contract_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '合同状态',
  `renew` tinyint NOT NULL DEFAULT '0' COMMENT '是否有续费：0、否；1、是；',
  `start_date` date DEFAULT NULL COMMENT '开始时间',
  `end_date` date DEFAULT NULL COMMENT '结束时间',
  `signing_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '签约状态',
  `contract_category` json DEFAULT NULL COMMENT '订单分类',
  `area_cascade` json DEFAULT NULL COMMENT '省市区',
  `contract_cate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '订单分类copy',
  `is_abnormal` tinyint NOT NULL DEFAULT '0' COMMENT '是否异常：1、是；0、否；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '修改时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='合同订单列表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_contract_doc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_contract_doc` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '经办人ID',
  `eid` int unsigned NOT NULL DEFAULT '0' COMMENT '关联客户ID',
  `cid` json DEFAULT NULL COMMENT '关联订单ID',
  `oid` json DEFAULT NULL COMMENT '关联商机ID',
  `link_type` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '关联类型:2.订单 5.商机',
  `doc_name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '合同名称',
  `doc_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '合同编号',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '合同状态:0.审批驳回;1.待审核;2.待签约;3.已签约;4.已拒绝;5.已过期;6.已撤销',
  `sign_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '签约方式:1.纸质签约 2.电子签约',
  `term_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '期限类型:0.无期限 1.固定期限 2.签约日起算',
  `date_count` int unsigned NOT NULL DEFAULT '0' COMMENT '期限时长',
  `start_date` datetime DEFAULT NULL COMMENT '合同开始日期',
  `end_date` datetime DEFAULT NULL COMMENT '合同结束日期',
  `sign_status` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '腾讯电子签状态',
  `sign_date` datetime DEFAULT NULL COMMENT '签约日期',
  `signature_sn` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '电子签订单号',
  `sign_file` json DEFAULT NULL COMMENT '签署前文件',
  `file_id` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '临时文件ID',
  `app_url` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '签署小程序链接',
  `pc_url` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '签署pc链接',
  `sign_url` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '签署后文件地址',
  `sign_result` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '签署后本地文件地址',
  `approve_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联审批ID',
  `is_verify` int unsigned NOT NULL DEFAULT '1' COMMENT '是否需要审核',
  `fail_time` timestamp NULL DEFAULT NULL COMMENT '合同截止日期',
  `mark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '备注信息',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='合同订单记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_contract_resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_contract_resource` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `eid` int NOT NULL DEFAULT '0' COMMENT '客户ID',
  `cid` int NOT NULL DEFAULT '0' COMMENT '合同ID',
  `uid` int NOT NULL DEFAULT '0' COMMENT '副表(admin)ID',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '备注内容',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='合同备注';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_contract_signatory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_contract_signatory` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cid` int unsigned NOT NULL COMMENT '合同id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '经办人姓名',
  `company_name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业名称',
  `phone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电话',
  `result` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '签约结果',
  `sign_time` timestamp NULL DEFAULT NULL COMMENT '签约时间',
  `sign_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '签约状态',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '人员类型:0、发起人 1、签署人 2、签署企业',
  `e_userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `e_openid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '备注',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_contract_signatory_cid_index` (`cid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='合同签署方';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_customer` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` int NOT NULL DEFAULT '0' COMMENT '业务员ID',
  `member` json DEFAULT NULL COMMENT '协作者',
  `userid` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企微用户ID',
  `external_userid` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企微客户ID',
  `before_uid` int NOT NULL DEFAULT '0' COMMENT '前业务员ID',
  `creator_uid` int NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `customer_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '客户名称',
  `customer_label` json DEFAULT NULL COMMENT '客户标签',
  `customer_no` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户编号',
  `customer_way` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '10' COMMENT '客户来源',
  `un_followed_days` int NOT NULL DEFAULT '0' COMMENT '未跟进天数',
  `amount_recorded` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '已入账金额',
  `amount_expend` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '已支出+金额',
  `invoiced_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '已开票金额',
  `contract_num` int NOT NULL DEFAULT '0' COMMENT '合同数量',
  `invoice_num` int NOT NULL DEFAULT '0' COMMENT '发票数量',
  `attachment_num` int NOT NULL DEFAULT '0' COMMENT '附件数量',
  `return_num` int NOT NULL DEFAULT '0' COMMENT '退回次数',
  `customer_followed` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '是否关注',
  `customer_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT '客户状态',
  `area_cascade` json DEFAULT NULL COMMENT '省市区',
  `customer_tel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '联系电话',
  `last_follow_up_time` timestamp NULL DEFAULT NULL COMMENT '最后跟进时间',
  `collect_time` timestamp NULL DEFAULT NULL COMMENT '领取时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_customer_clue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_customer_clue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '业务员用户ID',
  `before_uid` int unsigned NOT NULL DEFAULT '0' COMMENT '前业务员用户ID',
  `creator_uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '线索名称',
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '线索来源',
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '联系电话',
  `pool` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '线索池',
  `customer_label` json DEFAULT NULL COMMENT '客户标签',
  `createtime` date DEFAULT NULL COMMENT '线索时间',
  `area_cascade` json DEFAULT NULL COMMENT '省市区',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '详细地址',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1' COMMENT '线索状态',
  `followed` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1' COMMENT '是否关注',
  `return_num` int unsigned NOT NULL DEFAULT '0' COMMENT '退回次数',
  `claim_time` timestamp NULL DEFAULT NULL COMMENT '领取时间',
  `mark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '备注',
  `external_userid` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企微客户ID',
  `userid` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企微用户ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_customer_clue_uid_index` (`uid`) USING BTREE,
  KEY `eb_customer_clue_before_uid_index` (`before_uid`) USING BTREE,
  KEY `eb_customer_clue_creator_uid_index` (`creator_uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户线索表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_customer_liaison`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_customer_liaison` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` int NOT NULL DEFAULT '0' COMMENT '业务员ID',
  `eid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户名称',
  `creator_uid` int NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `liaison_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系人姓名',
  `liaison_tel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `liaison_job` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系人职位',
  `userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '企微用户ID',
  `external_userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企微客户ID',
  `liaison_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系人邮箱',
  `liaison_wechat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系人微信',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户联系人表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_customer_odds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_customer_odds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '业务员用户ID',
  `userid` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '企微用户ID',
  `external_userid` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '企微客户ID',
  `before_uid` int unsigned NOT NULL DEFAULT '0' COMMENT '前业务员用户ID',
  `creator_uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '' COMMENT '商机名称',
  `odds_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT '商机编号',
  `eid` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '' COMMENT '客户名称',
  `source` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '商机类型：1、线索；0、客户；',
  `types` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '' COMMENT '商机类型',
  `status` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '1' COMMENT '商机状态',
  `followed` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '1' COMMENT '是否关注',
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci COMMENT '商机描述',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_customer_odds_uid_index` (`uid`) USING BTREE,
  KEY `eb_customer_odds_before_uid_index` (`before_uid`) USING BTREE,
  KEY `eb_customer_odds_creator_uid_index` (`creator_uid`) USING BTREE,
  UNIQUE KEY `odds_no` (`odds_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='客户商机表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_customer_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_customer_product` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '' COMMENT '产品名称',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '产品分类',
  `path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '' COMMENT '产品分类',
  `unit_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '' COMMENT '单位名',
  `types` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '2' COMMENT '产品类型',
  `number` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '' COMMENT '产品编号',
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci COMMENT '产品简介',
  `spec_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '产品规格：0、单规格；1、多规格；',
  `is_show` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '1' COMMENT '产品状态',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `sort` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '排序',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_customer_product_uid_index` (`uid`) USING BTREE,
  KEY `eb_customer_product_name_index` (`name`) USING BTREE,
  KEY `eb_customer_product_number_index` (`number`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='产品表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_customer_product_assist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_customer_product_assist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `product_id` int unsigned NOT NULL DEFAULT '0' COMMENT '产品ID',
  `product_name` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '产品名',
  `image` varchar(512) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '产品图片',
  `sku` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `price` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '售价',
  `ot_price` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '原价',
  `total_price` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '总价',
  `count` int unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `discount` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '折扣百分比',
  `remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '备注',
  `unique` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '商品属性唯一值',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联ID',
  `link_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '关联业务类型:1、 客户；2、合同；3、联系人；4、线索；5、商机；6、产品；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_customer_product_assist_product_id_index` (`product_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='产品业务关联表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_customer_product_attr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_customer_product_attr` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `product_id` int unsigned NOT NULL DEFAULT '0' COMMENT '产品ID',
  `attr_name` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '属性名',
  `attr_values` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '属性值',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_customer_product_attr_product_id_index` (`product_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='产品规格表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_customer_product_attr_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_customer_product_attr_value` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `product_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商品ID',
  `detail` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '商品属性详情',
  `sku` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '商品属性索引值',
  `ot_price` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '原价',
  `price` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '售价',
  `cost` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本价',
  `image` varchar(512) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '图片',
  `bar_code` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '产品规格编码',
  `product_bar_code` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '产品条码',
  `unique` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '唯一值',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_customer_product_attr_value_product_id_index` (`product_id`) USING BTREE,
  KEY `eb_customer_product_attr_value_sku_index` (`sku`) USING BTREE,
  KEY `eb_customer_product_attr_value_unique_index` (`unique`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='产品属性值表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_customer_product_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_customer_product_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '路径',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `level` int unsigned NOT NULL DEFAULT '0' COMMENT '等级',
  `status` int unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_customer_product_category_uid_index` (`uid`) USING BTREE,
  KEY `eb_customer_product_category_pid_index` (`pid`) USING BTREE,
  KEY `eb_customer_product_category_name_index` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='产品分类表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_customer_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_customer_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `eid` int unsigned NOT NULL DEFAULT '0' COMMENT '关联业务ID',
  `type` tinyint NOT NULL COMMENT '记录类型 1、退回公海；2、领取；3、流失；4、取消流失；5、移交同事；',
  `link_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer' COMMENT '关联业务:customer:客户,contract:合同,invoice:发票,clue:线索,odds:商机,',
  `uid` int NOT NULL COMMENT '业务员ID',
  `creator_uid` int NOT NULL COMMENT '创建人ID',
  `record_version` int NOT NULL DEFAULT '0' COMMENT '记录版本',
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '原因',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eid` (`eid`,`link_type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_customer_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_customer_target` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户/部门ID',
  `amount` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '目标额',
  `year` int unsigned DEFAULT NULL COMMENT '年份',
  `month` int unsigned DEFAULT NULL COMMENT '月份',
  `types` int unsigned NOT NULL DEFAULT '0' COMMENT '类型：0、人员；1、部门；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_customer_target_uid_index` (`uid`) USING BTREE,
  KEY `eb_customer_target_link_id_index` (`link_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='业绩目标表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_daily_report_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_daily_report_member` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `daily_id` int NOT NULL COMMENT '汇报ID',
  `member` int NOT NULL DEFAULT '0' COMMENT '汇报人ID(admin自增ID)',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日报汇报人表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_dict_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_dict_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '数据名称',
  `value` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '数据值',
  `pid` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上级数据值',
  `type_id` int unsigned NOT NULL DEFAULT '0' COMMENT '字典类型ID',
  `type_name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '字典类型名称',
  `level` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '数据层级1、一级；2、二级；3、三级；4、四级；5、五级；',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `color` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '颜色',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1、开启；0、关闭；',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否默认：1、是；0、否；',
  `mark` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '备注信息',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_dict_data_type_name_index` (`type_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典数据表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_dict_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_dict_type` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '字典名称',
  `ident` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '字典标识',
  `link_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom' COMMENT '关联业务',
  `level` tinyint unsigned NOT NULL DEFAULT '4' COMMENT '数据最大层级1、一级；2、二级；3、三级；4、四级；5、五级；',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1、开启；0、关闭；',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否默认：1、是；0、否；',
  `mark` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '备注信息',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '实体id',
  `field_id` int NOT NULL DEFAULT '0' COMMENT '实体的字段id',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典类型表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_employee_train`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_employee_train` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '培训类型',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '数据详情',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='员工培训表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '企业表自增id',
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '公司logo',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理后台标题',
  `enterprise_name` varchar(51) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '公司名称',
  `short_name` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '公司简称',
  `enterprise_number` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '公司编号',
  `enterprise_name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '公司名称英文',
  `lead` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '法人代表',
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电话号',
  `phone` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `province` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所在省',
  `city` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所在城市',
  `area` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所在区',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '详细地址',
  `synopsis` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '简介',
  `fax` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '传真',
  `business_license` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '营业执照',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `disable_remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '禁用备注',
  `introduction` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '简介',
  `other` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '其他',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所属用户',
  `scale` int NOT NULL DEFAULT '0' COMMENT '公司规模',
  `type` int NOT NULL DEFAULT '0' COMMENT '企业类型',
  `level` int NOT NULL DEFAULT '0' COMMENT '企业等级',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `verify` tinyint NOT NULL DEFAULT '0' COMMENT '0=审核,1=审核通过,-1=不通过',
  `remind` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '提醒状态：0、未读；1、已读；',
  `uniqued` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业唯一值',
  `init_data` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否已加载默认数据',
  `disk_size` bigint NOT NULL DEFAULT '0' COMMENT '已使用云盘空间',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '0=禁用,1=正常,2=待缴费,3=已过期',
  `delete` timestamp NULL DEFAULT NULL COMMENT '是否删除',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业信息表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `key` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置字段',
  `key_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名称',
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '类型(文本框,单选按钮...)',
  `input_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'input' COMMENT '表单类型',
  `category` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置分类:assess、绩效考核',
  `parameter` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则 单选框和多选框',
  `upload_type` tinyint NOT NULL DEFAULT '0' COMMENT '上传文件格式1单图2多图3文件',
  `required` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则',
  `width` int NOT NULL DEFAULT '0' COMMENT '多行文本框的宽度',
  `high` int NOT NULL DEFAULT '0' COMMENT '多行文框的高度',
  `value` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '默认值',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置简介',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `entid` bigint unsigned NOT NULL COMMENT '企业id',
  `is_show` tinyint NOT NULL DEFAULT '0' COMMENT '是否隐藏',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_config_entid_index` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业配置表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_file` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件名称',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件封面',
  `real_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件原始名称',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件夹路径',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访问完整路径',
  `file_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件标识',
  `size` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '文件大小(单位KB)',
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件类型',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拥有人UID',
  `edit_uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '修改人UID',
  `version` int NOT NULL DEFAULT '0' COMMENT '文件版本号',
  `other` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '其他参数',
  `cate_id` int NOT NULL DEFAULT '0' COMMENT '分类ID',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `download_count` int NOT NULL DEFAULT '0' COMMENT '下载次数',
  `upload_type` tinyint NOT NULL DEFAULT '0' COMMENT '上传文件驱动类型1=本地,2=七牛,3=oss,4=cos',
  `is_master` tinyint NOT NULL DEFAULT '0' COMMENT '是否是主文件',
  `is_template` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是模板',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  `delete` timestamp NULL DEFAULT NULL COMMENT '是否删除',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `version` (`file_id`,`version`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业文件表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_file_change`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_file_change` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `file_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件标识',
  `version` int NOT NULL DEFAULT '0' COMMENT '文件版本号',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户id(admin自增ID)',
  `change_message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '变动说明',
  `change_time` timestamp NULL DEFAULT NULL COMMENT '变动时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业文件变动记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_file_folder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_file_folder` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件夹名称',
  `entid` bigint unsigned NOT NULL COMMENT '企业id',
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级文件夹ID',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路径',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_file_folder_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业文件夹表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_file_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_file_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户id(admin自增ID)',
  `file_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件标识',
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `type` enum('write','read') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '权限类型',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_file_permissions_uid_foreign` (`uid`) USING BTREE,
  KEY `eb_enterprise_file_permissions_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业文件权限表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '用户ID',
  `user_name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员姓名',
  `path` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '链接',
  `method` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '访问方式',
  `event_name` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '行为',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `type` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '类型',
  `terminal` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '访问终端',
  `last_ip` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '访问ip',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `entid_uid` (`uid`,`entid`) USING BTREE,
  KEY `eb_enterprise_log_uid_index` (`uid`) USING BTREE,
  KEY `eb_enterprise_log_user_name_index` (`user_name`) USING BTREE,
  KEY `eb_enterprise_log_entid_index` (`entid`) USING BTREE,
  KEY `eb_enterprise_log_type_index` (`type`) USING BTREE,
  KEY `eb_enterprise_log_terminal_index` (`terminal`) USING BTREE,
  KEY `idx_entid_id` (`entid`,`id`) USING BTREE,
  KEY `idx_entid_created_id` (`entid`,`created_at`,`id`) USING BTREE,
  KEY `idx_created_id` (`created_at`,`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='企业日志表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单自增id',
  `menu_id` bigint unsigned NOT NULL COMMENT '菜单id',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '1=权限0=菜单',
  `is_show` tinyint NOT NULL DEFAULT '1' COMMENT '是否为隐藏菜单供前台使用',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '菜单状态 1=开启,0=关闭',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `is_admin` (`status`,`entid`) USING BTREE,
  KEY `eb_enterprise_menus_menu_id_foreign` (`menu_id`) USING BTREE,
  KEY `eb_enterprise_menus_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业菜单表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_message_notice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_message_notice` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `send_id` int NOT NULL DEFAULT '0' COMMENT '发送人或者企业ID',
  `to_uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '接收人ID',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '跳转链接',
  `uni_url` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'uni跳转路径',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图片',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '消息标题',
  `message` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '消息内容',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '消息类型:1=系统消息;0=个人消息;3=企业站内消息',
  `cate_id` int NOT NULL DEFAULT '0' COMMENT '消息类型',
  `message_id` int unsigned NOT NULL DEFAULT '0' COMMENT '消息模板ID',
  `cate_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '消息类型名称',
  `is_read` tinyint NOT NULL DEFAULT '0' COMMENT '是否已读:1=已读;0=未读',
  `is_handle` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已处理',
  `is_show` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  `template_type` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '消息类型',
  `button_template` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '消息类型',
  `other` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '其他附加消息内容',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联记录ID',
  `link_status` int NOT NULL DEFAULT '0' COMMENT '关联记录状态',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_message_notice_entid_foreign` (`entid`) USING BTREE,
  KEY `eb_enterprise_message_notice_to_uid_foreign` (`to_uid`) USING BTREE,
  KEY `template_type` (`template_type`) USING BTREE,
  KEY `idx_to_uid_is_read_cate_id` (`to_uid`,`is_read`,`cate_id`),
  KEY `idx_to_uid_cate_id_created_at` (`to_uid`,`cate_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业消息通知表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_notice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_notice` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `cate_id` int NOT NULL DEFAULT '0' COMMENT '分类ID',
  `card_id` bigint unsigned NOT NULL COMMENT '卡片ID',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `title` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通知标题',
  `cover` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '封面图',
  `info` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通知简介',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容详情',
  `is_top` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否置顶',
  `push_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '发布类型：0、立即；1、定时；',
  `push_time` timestamp NOT NULL COMMENT '发布时间',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `visit` int unsigned NOT NULL DEFAULT '0' COMMENT '浏览量',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `entid` (`entid`) USING BTREE,
  KEY `eb_enterprise_notice_card_id_foreign` (`card_id`) USING BTREE,
  KEY `eb_enterprise_notice_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业公告表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_notice_visit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_notice_visit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建用户ID',
  `notice_id` bigint unsigned NOT NULL COMMENT '公告ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_notice_visit_uuid_index` (`user_id`) USING BTREE,
  KEY `eb_enterprise_notice_visit_notice_id_foreign` (`notice_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业公告浏览记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_paytype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_paytype` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `type_id` int NOT NULL DEFAULT '0' COMMENT '支付方式ID',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付方式名称',
  `ident` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付方式标识',
  `info` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '简介',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '是否可用：1、是；0、否；',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业支付方式表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '角色自增id',
  `role_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色名称',
  `types` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '角色类型，null为用户自己添加',
  `user_count` int NOT NULL DEFAULT '0' COMMENT '用户数量',
  `entid` int NOT NULL COMMENT '企业ID',
  `data_level` int unsigned NOT NULL DEFAULT '1' COMMENT '数据范围：见枚举；',
  `directly` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否包含直属下级；',
  `frame_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '指定部门ID；',
  `module_permissions` json DEFAULT NULL COMMENT '内置模块数据权限配置',
  `rules` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '身份管理权限(system_menus主键ID)',
  `rule_unique` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '菜单标识',
  `apis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '身份管理接口权限(system_menus主键ID)',
  `api_unique` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '接口标识',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_role_entid_index` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业角色表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_role_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `role_id` int NOT NULL DEFAULT '0' COMMENT '角色(enterprise_role主键)iD',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户关联企业表(admin主键)ID',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态1=开启;0=关闭',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_role_user_role_id_index` (`role_id`) USING BTREE,
  KEY `eb_enterprise_role_user_user_id_index` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业角色用户关联表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_target` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '创建人ID',
  `cate_id` int NOT NULL DEFAULT '0' COMMENT '分类ID',
  `name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '指标名称',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '指标内容',
  `status` int NOT NULL DEFAULT '0' COMMENT '开放状态：0、不开放；1、开放；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_target_entid_foreign` (`entid`) USING BTREE,
  KEY `eb_enterprise_target_uid_foreign` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业考核指标模板表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_target_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_target_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路径',
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '职级类别名称',
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级ID',
  `types` int NOT NULL DEFAULT '0' COMMENT '类型：0、指标分类；1、指标模板分类；',
  `status` int NOT NULL DEFAULT '0' COMMENT '开放状态：0、不开放；1、开放；',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_target_category_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业考核指标模版分类表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_template` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '企业用户ID',
  `cate_id` int NOT NULL DEFAULT '0' COMMENT '模板分类ID',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板简介',
  `cover` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '封面图',
  `color` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#000000' COMMENT '默认字体颜色',
  `status` int NOT NULL DEFAULT '0' COMMENT '开放状态：0、不开放；1、开放；',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '记分类型：0，加权评分；1，加和评分',
  `way` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '来源：0、企业端；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业考核模板表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_template_collect`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_template_collect` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '企业用户ID',
  `temp_id` int NOT NULL DEFAULT '0' COMMENT '考核模板ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业考核模板收藏表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_user_card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_user_card` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `entid` bigint unsigned NOT NULL COMMENT '企业ID',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户头像',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '员工姓名',
  `letter` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '#' COMMENT '姓氏首字母',
  `phone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `position` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '职位',
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '城市',
  `area` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '区域',
  `card_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证号',
  `province` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '省份',
  `birthday` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '员工生日',
  `nation` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '员工种族',
  `politic` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '政治面貌',
  `education` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学历',
  `education_image` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学历证书',
  `acad` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学位',
  `acad_image` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学位证书',
  `native` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '籍贯',
  `address` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '居住地',
  `sex` tinyint NOT NULL DEFAULT '0' COMMENT '性别: 0、未知；1、男；2、女；3、其他；',
  `age` tinyint unsigned DEFAULT NULL COMMENT '员工年龄',
  `marriage` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '婚姻状况:0、未婚；1、已婚；',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '员工状态:0、未入职；1、正式;2、使用;3、实习;4、离职；',
  `work_years` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '工作经验（年）',
  `spare_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '紧急联系人',
  `spare_tel` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '紧急联系电话',
  `email` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `social_num` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '社保账户',
  `fund_num` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '公积金账户',
  `bank_num` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '银行卡账户',
  `bank_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行',
  `graduate_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '毕业院校',
  `graduate_date` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '毕业时间',
  `interview_date` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '面试时间',
  `interview_position` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '面试职位',
  `is_part` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否兼职',
  `photo` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '员工照片',
  `card_front` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证正面',
  `card_both` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证背面',
  `work_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '入职时间',
  `trial_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '试用时间',
  `formal_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '转正时间',
  `treaty_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '合同到期时间',
  `quit_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '离职时间',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：1、正常；0、未激活；2、锁定；',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `u_statu` (`uid`,`entid`,`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业用户信息表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_user_change`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_user_change` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '人员ID',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `card_id` int unsigned NOT NULL DEFAULT '0' COMMENT '企业用户名片ID',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '变动类型：0、入职；1、转正；2、调岗；3、离职；',
  `date` date DEFAULT NULL COMMENT '变动时间',
  `new_frame` int unsigned NOT NULL DEFAULT '0' COMMENT '新部门ID',
  `old_frame` int unsigned NOT NULL DEFAULT '0' COMMENT '原部门ID',
  `new_position` int unsigned NOT NULL DEFAULT '0' COMMENT '新职位ID',
  `old_position` int unsigned NOT NULL DEFAULT '0' COMMENT '原职位ID',
  `info` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '原因说明',
  `mark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注信息',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联申请单ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '转移人员ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业用户变动记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_user_daily`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_user_daily` (
  `daily_id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL DEFAULT '1' COMMENT '企业ID',
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '副表(admin)ID',
  `finish` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '工作总结',
  `plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '工作计划',
  `mark` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注信息',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '提交状态：0、未提交；1、已提交',
  `types` tinyint NOT NULL DEFAULT '0' COMMENT '报告类型：0、日报；1、周报；2、月报',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`daily_id`) USING BTREE,
  KEY `eb_enterprise_user_daily_entid_foreign` (`entid`) USING BTREE,
  KEY `eb_enterprise_user_daily_uid_foreign` (`uid`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业用户汇报表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_user_daily_reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_user_daily_reply` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `pid` bigint unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `daily_id` bigint unsigned NOT NULL COMMENT '日报ID',
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `content` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '回复内容',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_user_daily_reply_pid_foreign` (`pid`) USING BTREE,
  KEY `eb_enterprise_user_daily_reply_daily_id_foreign` (`daily_id`) USING BTREE,
  KEY `eb_enterprise_user_daily_reply_uid_foreign` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业用户汇报回复表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_user_education`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_user_education` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `card_id` bigint unsigned NOT NULL COMMENT '企业用户信息(enterprise_user_card)ID',
  `start_time` date DEFAULT NULL COMMENT '开始时间',
  `end_time` date DEFAULT NULL COMMENT '结束时间',
  `school_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学校名称',
  `major` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所学专业',
  `education` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学历',
  `academic` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学位',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_user_education_card_id_foreign` (`card_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业用户教育经历表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_user_job_analysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_user_job_analysis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `uid` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分析内容',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_user_job_analysis_entid_index` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业用户工作分析表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_user_position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_user_position` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `card_id` bigint unsigned NOT NULL COMMENT '企业用户信息(enterprise_user_card)ID',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '开始时间',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '结束时间',
  `position` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '职位',
  `department` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '部门',
  `is_admin` tinyint NOT NULL DEFAULT '0' COMMENT '身份0=普通员工;1=主管',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '任职状态0=离职;1=任职',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_user_position_card_id_foreign` (`card_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业用户职位信息表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_user_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '权限自增id',
  `user_id` int NOT NULL COMMENT '企业成员ID(admin主键ID)',
  `rules` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '身份管理权限(system_menus主键ID)',
  `apis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '身份管理接口权限(system_menus主键ID)',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_user_role_user_id_index` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业用户权限表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_user_salary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_user_salary` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `card_id` int unsigned NOT NULL DEFAULT '0' COMMENT '企业用户名片ID',
  `total` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '变更内容',
  `take_date` date DEFAULT NULL COMMENT '生效时间',
  `content` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '变更内容',
  `mark` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '变更原因',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联申请单ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业用户薪资变动记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_user_scope`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_user_scope` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联ID',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0、组织架构；1、用户；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业用户权限范围表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_enterprise_user_work`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_enterprise_user_work` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `card_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '企业用户信息(enterprise_user_card)ID',
  `start_time` date DEFAULT NULL COMMENT '开始时间',
  `end_time` date DEFAULT NULL COMMENT '结束时间',
  `company` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所在公司',
  `position` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '职位',
  `describe` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '工作描述',
  `quit_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '离职原因',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_user_work_card_id_foreign` (`card_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业用户工作经历表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_export_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_export_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` int unsigned NOT NULL COMMENT '用户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件名',
  `success_count` int unsigned NOT NULL DEFAULT '0' COMMENT '成功数量',
  `fail_count` int unsigned NOT NULL DEFAULT '0' COMMENT '失败数量',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态:0、待处理,1、成功,2、失败',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '类型:0、导出;1、导入;',
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件路径',
  `file_status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '文件状态:0、正常,1、删除',
  `fail_msg` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '失败原因',
  `module` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '关联业务模块',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='导入导出记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_faguohou`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_faguohou` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0' COMMENT '创建用户id',
  `update_user_id` int NOT NULL DEFAULT '0' COMMENT '修改用户id',
  `owner_user_id` int NOT NULL DEFAULT '0' COMMENT '所属用户id',
  `frame_id` int NOT NULL DEFAULT '0' COMMENT '部门ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务id',
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '数据库连接配置',
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '队列名称',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务负载',
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '异常信息',
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '失败时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `eb_failed_jobs_uuid_unique` (`uuid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='失败任务表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_folder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_folder` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '文件 id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0:文件 1:目录',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件名称',
  `path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件路径',
  `pid` bigint unsigned NOT NULL DEFAULT '0' COMMENT '父级文件 id',
  `uid` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户 id',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文件真实名称',
  `file_ext` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '文件后缀',
  `file_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文件 url',
  `file_sn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文件编号',
  `file_size` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文件大小',
  `file_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文件类型',
  `upload_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '上传类型',
  `entid` bigint unsigned DEFAULT '0' COMMENT '企业 id',
  `download_count` int unsigned DEFAULT '0' COMMENT '下载次数',
  `version` int unsigned DEFAULT '1' COMMENT '文件版本',
  `is_temp` tinyint unsigned DEFAULT '0' COMMENT '临时文件',
  `is_share` tinyint unsigned DEFAULT '0' COMMENT '是否共享',
  `is_collect` tinyint unsigned DEFAULT '0' COMMENT '是否收藏',
  `is_shortcut` tinyint unsigned DEFAULT '0' COMMENT '是否常用',
  `is_del` tinyint unsigned DEFAULT '0' COMMENT '是否删除',
  `del_uid` int unsigned NOT NULL DEFAULT '0' COMMENT '删除用户id',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件夹表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_folder_auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_folder_auth` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '文件权限 id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `folder_id` bigint unsigned NOT NULL COMMENT '文件 id',
  `uid` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户 id',
  `create` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '目录管理权限',
  `read` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '查看权限',
  `update` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '编辑权限',
  `download` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '下载权限',
  `delete` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '删除权限',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件夹权限表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_folder_collaborate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_folder_collaborate` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `uid` int NOT NULL COMMENT '业务员ID',
  `folder_id` bigint unsigned NOT NULL COMMENT '文件ID',
  `update` tinyint NOT NULL DEFAULT '0' COMMENT '更新权限',
  `uniqued` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '校验码',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件夹协作表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_folder_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_folder_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '历史记录 id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `entid` bigint unsigned DEFAULT '0' COMMENT '企业 id',
  `uid` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '修改用户',
  `folder_id` bigint unsigned NOT NULL COMMENT '文件 id',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件真实名称',
  `file_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件 url',
  `file_size` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件大小',
  `version` int unsigned NOT NULL DEFAULT '1' COMMENT '文件版本',
  `download_count` int unsigned DEFAULT '0' COMMENT '下载次数',
  `upload_type` tinyint NOT NULL COMMENT '上传方式',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件夹历史记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_folder_share`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_folder_share` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '共享 id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `folder_id` bigint unsigned NOT NULL COMMENT '文件 id',
  `auth_id` bigint unsigned NOT NULL COMMENT '权限 id',
  `to_uid` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '共享用户',
  `entid` bigint unsigned DEFAULT '0' COMMENT '企业 id/用户 id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '共享时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件夹共享表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_folder_view_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_folder_view_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `uid` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '修改用户',
  `folder_id` bigint unsigned NOT NULL COMMENT '文件 id',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件真实名称',
  `file_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件 url',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_folder_view_hitory_uid_index` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件夹查看历史记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_form_cate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_form_cate` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组名称',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '分组排序',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '分组类型：1、客户；2、合同；3、联系人；',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1、显示；0、隐藏；',
  `ident` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标识',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单分类表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_form_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_form_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '字段唯一值',
  `key_name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段名称',
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '类型(文本框,单选按钮...)',
  `input_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'input' COMMENT '表单类型',
  `cate_id` int unsigned NOT NULL DEFAULT '0' COMMENT '配置分类id',
  `param` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则 单选框和多选框',
  `decimal_place` tinyint NOT NULL DEFAULT '0' COMMENT '数字字段小数位数',
  `upload_type` tinyint NOT NULL DEFAULT '0' COMMENT '上传文件格式1单图2多图3文件',
  `required` tinyint NOT NULL DEFAULT '0' COMMENT '是否必填：1、必填；0、非必填；',
  `placeholder` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提示文字',
  `max` int NOT NULL DEFAULT '0' COMMENT '最大边界值',
  `min` int NOT NULL DEFAULT '0' COMMENT '最小边界值',
  `dict_ident` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '字典标识',
  `value` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '默认值',
  `uniqued` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否校验唯一',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置简介',
  `link_type` tinyint NOT NULL DEFAULT '0' COMMENT '关联类型：1、客户；2、合同；3、发票；4、线索；5、商机；6、产品；',
  `link_field` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联字段',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：1、显示；2、隐藏；',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单数据表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_frame`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_frame` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '部门主管ID',
  `entid` bigint unsigned NOT NULL,
  `pid` int NOT NULL DEFAULT '0' COMMENT '父级ID',
  `role_id` int NOT NULL DEFAULT '0' COMMENT '角色ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '部门名称',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路径',
  `introduce` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '部门介绍',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `user_count` int NOT NULL DEFAULT '0' COMMENT '用户数量',
  `user_single_count` int NOT NULL DEFAULT '0' COMMENT '单个部门总人数',
  `is_show` tinyint NOT NULL DEFAULT '1' COMMENT '是否显示',
  `level` int NOT NULL DEFAULT '0' COMMENT '等级',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `show_cate` (`id`,`is_show`) USING BTREE,
  KEY `eb_enterprise_frame_entid_index` (`entid`) USING BTREE,
  KEY `eb_enterprise_frame_pid_index` (`pid`) USING BTREE,
  KEY `path` (`path`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业部门表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_frame_assist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_frame_assist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '辅助表自增id',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `frame_id` int NOT NULL DEFAULT '0' COMMENT '主表(enterprise_frame)ID',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '副表(admin)ID',
  `is_mastart` tinyint NOT NULL DEFAULT '0' COMMENT '是否为主部门',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为该部门的主管',
  `superior_uid` int unsigned NOT NULL DEFAULT '0' COMMENT '上级主管用户ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '添加时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `frame_id` (`frame_id`,`user_id`) USING BTREE,
  KEY `eb_enterprise_frame_assist_frame_id_index` (`frame_id`) USING BTREE,
  KEY `eb_enterprise_frame_assist_user_id_index` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业部门和员工关联表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_gongzitiaojiegou`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_gongzitiaojiegou` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0' COMMENT '创建用户id',
  `update_user_id` int NOT NULL DEFAULT '0' COMMENT '修改用户id',
  `owner_user_id` int NOT NULL DEFAULT '0' COMMENT '所属用户id',
  `jibengongzi` decimal(10,2) DEFAULT NULL COMMENT '基本工资',
  `jixiaogongzi` decimal(10,2) DEFAULT NULL COMMENT '绩效工资',
  `gangweigongzi` decimal(10,2) DEFAULT NULL COMMENT '岗位工资',
  `guanlijintie` decimal(10,2) DEFAULT NULL COMMENT '管理津贴',
  `jinengbutie` decimal(10,2) DEFAULT NULL COMMENT '技能补贴',
  `qitabutie` decimal(10,2) DEFAULT NULL COMMENT '其他补贴',
  `yuangong` int DEFAULT NULL COMMENT '员工',
  `tiaoxinbeizhu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '调薪备注',
  `frame_id` int NOT NULL DEFAULT '0' COMMENT '部门ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工资条结构表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_gongzitiaojilu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_gongzitiaojilu` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0' COMMENT '创建用户id',
  `update_user_id` int NOT NULL DEFAULT '0' COMMENT '修改用户id',
  `owner_user_id` int NOT NULL DEFAULT '0' COMMENT '所属用户id',
  `gongzitiaojiegou` int DEFAULT NULL COMMENT '工资条结构',
  `jibengongzi` decimal(10,2) DEFAULT NULL COMMENT '基本工资',
  `jixiaogongzi` decimal(10,2) DEFAULT NULL COMMENT '绩效工资',
  `gangweigongzi` decimal(10,2) DEFAULT NULL COMMENT '岗位工资',
  `guanlijintie` decimal(10,2) DEFAULT NULL COMMENT '管理津贴',
  `jinengbutie` decimal(10,2) DEFAULT NULL COMMENT '技能补贴',
  `qitabutie` decimal(10,2) DEFAULT NULL COMMENT '其他补贴',
  `yuangong` int NOT NULL DEFAULT '0' COMMENT '员工',
  `tiaoxinbeizhu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '调薪备注',
  `frame_id` int NOT NULL DEFAULT '0' COMMENT '部门ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工资调整记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_hay_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_hay_group` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '评估表名称',
  `uid` int NOT NULL COMMENT '业务员ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='海氏评估表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_hay_group_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_hay_group_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int unsigned NOT NULL COMMENT '评估表ID',
  `uid` int NOT NULL COMMENT '业务员ID',
  `col1` int NOT NULL COMMENT '职位',
  `col2` int NOT NULL COMMENT '专业知识水平',
  `col3` int NOT NULL COMMENT '管理诀窍',
  `col4` int NOT NULL COMMENT '人际关系技巧',
  `col5` int NOT NULL COMMENT '评分',
  `col6` int NOT NULL COMMENT '思维环境',
  `col7` int NOT NULL COMMENT '思维难度',
  `col8` int NOT NULL COMMENT '评分',
  `col9` int NOT NULL COMMENT '行动自由度',
  `col10` int NOT NULL COMMENT '职务责任',
  `col11` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '职务影响结果',
  `col12` int NOT NULL COMMENT '评分',
  `col13` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'α',
  `col14` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'β',
  `col15` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '岗位分数',
  `col16` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '岗位系数',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='海氏评估表数据';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_id_card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_id_card` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL COMMENT '用户ID',
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文件名',
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'MIME类型',
  `file_size` decimal(10,2) DEFAULT NULL COMMENT '文件大小',
  `front_image` blob COMMENT '正面图片',
  `back_image` blob COMMENT '背面图片',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户身份证表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_message` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业id',
  `relation_id` int NOT NULL DEFAULT '0' COMMENT '总平台ID',
  `cate_id` int NOT NULL DEFAULT '0' COMMENT '类型ID',
  `cate_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `template_type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联通知类型',
  `template_var` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '模板变量',
  `template_time` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '消息标题',
  `content` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '消息内容',
  `remind_time` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提醒时间',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '实体id',
  `event_id` int NOT NULL DEFAULT '0' COMMENT '实体的触发器id',
  `user_sub` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '用户可取消订阅',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `template_type` (`template_type`) USING BTREE,
  KEY `eb_message_crud_id_index` (`crud_id`) USING BTREE,
  KEY `eb_message_event_id_index` (`event_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息设置表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_message_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_message_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `pid` int NOT NULL DEFAULT '0' COMMENT '父级ID',
  `cate_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路径',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `pic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标',
  `is_show` tinyint NOT NULL DEFAULT '1' COMMENT '是否显示',
  `uni_show` tinyint NOT NULL DEFAULT '1' COMMENT '移动端是否显示',
  `level` int NOT NULL DEFAULT '0' COMMENT '等级',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_message_category_pid_index` (`pid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息分类表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_message_subscribe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_message_subscribe` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT '企业用户ID',
  `message_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '消息ID',
  `is_subscribe` int unsigned NOT NULL DEFAULT '0' COMMENT '订阅/取消订阅',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息订阅表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_message_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_message_template` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `relation_id` int NOT NULL DEFAULT '0' COMMENT '总平台ID',
  `message_id` int NOT NULL DEFAULT '0' COMMENT '系统消息id',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '类型:0=系统消息;1=短信消息',
  `template_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板id,可以为短信模板',
  `message_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '消息标题',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '消息图片',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '跳转标题',
  `uni_url` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '移动端跳转链接',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '开启状态:0=关闭;1=开启',
  `webhook_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'bot webhook地址',
  `relation_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '系统消息状态',
  `content_template` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '内容模板',
  `button_template` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '按钮模板',
  `push_rule` tinyint NOT NULL DEFAULT '0' COMMENT '推送规则:0=即时推送;1=延迟推送',
  `minute` int NOT NULL DEFAULT '0' COMMENT '几分钟后推送',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `crud_event_id` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `message_id_type` (`message_id`,`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息模板表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '迁移名称',
  `batch` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='迁移表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_openapi_key`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_openapi_key` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '对外接口名称',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `ak` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '对外接口AK',
  `sk` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '对外接口SK',
  `info` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1、启用；0、禁用；',
  `last_time` timestamp NULL DEFAULT NULL COMMENT '最近登录时间',
  `last_ip` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '最近登录IP',
  `auth` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '接口权限（系统）',
  `crud_auth` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '接口权限（实体）',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `eb_openapi_key_ak_unique` (`ak`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='对外接口表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_openapi_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_openapi_rule` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '权限名称',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '0=分类，1=接口',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '实体id',
  `method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '请求方式',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '请求地址',
  `path_prams` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '请求参数',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `get_prams` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'GET请求参数',
  `post_prams` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'POST请求参数',
  `request_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '请求数据',
  `response_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '响应数据',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='对外接口规则表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_program` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `ident` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '编号',
  `uid` bigint NOT NULL DEFAULT '0' COMMENT '负责人',
  `eid` bigint NOT NULL DEFAULT '0' COMMENT '关联客户',
  `cid` bigint NOT NULL DEFAULT '0' COMMENT '关联合同',
  `creator_uid` bigint NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `start_date` date DEFAULT NULL COMMENT '开始时间',
  `end_date` date DEFAULT NULL COMMENT '结束时间',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '项目状态：0：正常；1：暂停；2：关闭；',
  `describe` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '项目描述',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='项目表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_program_dynamic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_program_dynamic` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `types` tinyint NOT NULL DEFAULT '0' COMMENT '动态类型 1：项目；2：任务；',
  `uid` bigint NOT NULL COMMENT '操作人ID',
  `operator` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作人姓名',
  `relation_id` bigint NOT NULL COMMENT '操作ID',
  `action_type` tinyint NOT NULL DEFAULT '0' COMMENT '动作类型 1：创建；2：修改；',
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '操作说明',
  `describe` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '描述',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='项目动态表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_program_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_program_member` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `program_id` bigint NOT NULL COMMENT '项目ID',
  `uid` bigint NOT NULL COMMENT '项目成员',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='项目成员表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_program_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_program_task` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '任务名称',
  `ident` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '任务编号',
  `pid` bigint NOT NULL COMMENT '父级ID',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '路径',
  `top_id` bigint NOT NULL COMMENT '顶级ID',
  `level` int NOT NULL COMMENT '级别',
  `program_id` bigint NOT NULL COMMENT '项目ID',
  `version_id` bigint NOT NULL COMMENT '版本ID',
  `creator_uid` bigint NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `uid` bigint NOT NULL DEFAULT '0' COMMENT '负责人',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '项目状态：0：未处理；1：进行中；2：已解决；3：已验收；4：已拒绝；',
  `priority` tinyint NOT NULL DEFAULT '0' COMMENT '优先级：1：紧急；2：高；3：中；4：低；',
  `plan_start` date DEFAULT NULL COMMENT '计划开始',
  `plan_end` date DEFAULT NULL COMMENT '计划结束',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `describe` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务描述',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='项目任务表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_program_task_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_program_task_comment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `task_id` bigint NOT NULL COMMENT '任务ID',
  `pid` bigint NOT NULL COMMENT '父级ID',
  `reply_uid` bigint NOT NULL COMMENT '回复评论人ID',
  `uid` bigint NOT NULL COMMENT '评论人ID',
  `describe` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '描述',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='项目任务评论表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_program_task_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_program_task_member` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `task_id` bigint NOT NULL COMMENT '任务ID',
  `uid` bigint NOT NULL COMMENT '项目成员',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='项目任务成员表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_program_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_program_version` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `program_id` bigint NOT NULL COMMENT '项目ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '版本名称',
  `creator_uid` bigint NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='项目版本表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_promotion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '晋升名称',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1、展示; 0、关闭',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='晋升岗位表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_promotion_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_promotion_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `promotion_id` int unsigned NOT NULL COMMENT '晋升表ID',
  `rank` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '职级',
  `position` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '职位',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '合计',
  `benefit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '效益工资',
  `standard` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标准',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='晋升规则表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_rank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_rank` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '职级名称',
  `entid` bigint unsigned NOT NULL COMMENT '企业id',
  `cate_id` bigint unsigned NOT NULL COMMENT '职级类别id',
  `card_id` bigint unsigned NOT NULL COMMENT '卡片id',
  `alias` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '职级别名',
  `info` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '职级描述',
  `number` int NOT NULL DEFAULT '0' COMMENT '职级人数',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:1=开启,0=关闭',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `list` (`entid`,`cate_id`,`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='职级表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_rank_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_rank_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '职级类别名称',
  `number` int NOT NULL DEFAULT '0' COMMENT '职级数',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_rank_category_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='职级类别表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_rank_job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_rank_job` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `entid` bigint unsigned NOT NULL ,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '职位名称',
  `cate_id` bigint unsigned NOT NULL COMMENT '职级类别id',
  `rank_id` bigint unsigned NOT NULL COMMENT '职级id',
  `card_id` bigint unsigned NOT NULL COMMENT '卡片id',
  `job_count` int NOT NULL DEFAULT '0' COMMENT '岗位人数',
  `describe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '岗位描述',
  `duty` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '岗位职责',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0=关闭;1=开启',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_rank_job_entid_foreign` (`entid`) USING BTREE,
  KEY `eb_enterprise_rank_job_cate_id_foreign` (`cate_id`) USING BTREE,
  KEY `eb_enterprise_rank_job_rank_id_foreign` (`rank_id`) USING BTREE,
  KEY `eb_enterprise_rank_job_card_id_foreign` (`card_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='职级岗位表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_rank_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_rank_level` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` bigint unsigned NOT NULL COMMENT '企业id',
  `salary` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '薪资范围',
  `min_level` int NOT NULL DEFAULT '0' COMMENT '职等最小值',
  `max_level` int NOT NULL DEFAULT '0' COMMENT '职等最大值',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_enterprise_rank_level_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='职等表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_rank_relation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_rank_relation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `level_id` bigint unsigned NOT NULL COMMENT '职等ID',
  `cate_id` bigint unsigned NOT NULL COMMENT '职级类别ID',
  `rank_id` bigint unsigned NOT NULL COMMENT '职级ID',
  `number` int NOT NULL DEFAULT '0' COMMENT '职级数',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:1=开启,0=关闭',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='职级关系表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_roster_cycle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_roster_cycle` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int unsigned NOT NULL COMMENT '考勤组ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '周期名称',
  `cycle` int unsigned NOT NULL DEFAULT '0' COMMENT '周期',
  `uid` int NOT NULL COMMENT '业务员ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤周期表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_roster_cycle_shift`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_roster_cycle_shift` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `cycle_id` int NOT NULL COMMENT '周期ID',
  `shift_id` int NOT NULL COMMENT '班次ID',
  `number` int NOT NULL COMMENT '周期数',
  `uid` int NOT NULL COMMENT '业务员ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤周期班次表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_rules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ptype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `v0` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `v1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `v2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `v3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `v4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `v5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Casbin权限规则表';
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_salesman_custom_field` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `custom_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '类型',
  `field_list` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '自定义数据',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='业务员自定义字段表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_schedule` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业用户ID',
  `cid` int NOT NULL DEFAULT '0' COMMENT '日程分类ID',
  `color` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '日程分类颜色',
  `title` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '日程标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '日程内容',
  `all_day` int unsigned NOT NULL DEFAULT '0' COMMENT '是否全天：1、是；0、否；',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '开始时间',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '结束时间',
  `period` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '重复周期：0、不重复；1、日；2、月；3、年；',
  `rate` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '重复频率',
  `days` json DEFAULT NULL COMMENT '重复星期/日期',
  `remind` tinyint NOT NULL DEFAULT '1' COMMENT '是否提醒：1、是；0、否；',
  `fail_time` timestamp NULL DEFAULT NULL COMMENT '结束时间',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '关联日程ID',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联业务ID',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '日程状态：0、待定；1、接受；2、拒绝；3、完成',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日程表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_schedule_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_schedule_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户ID',
  `schedule_id` int NOT NULL DEFAULT '0' COMMENT '提醒ID',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '完成状态：1、是；0、否；',
  `remind_day` date DEFAULT NULL COMMENT '提醒日期',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日程记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_schedule_remind`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_schedule_remind` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `sid` int unsigned NOT NULL DEFAULT '0' COMMENT '关联日程ID',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户ID',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `types` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '类型：user、用户；assess、考核；',
  `content` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '待办内容',
  `mark` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注信息',
  `period` tinyint NOT NULL DEFAULT '0' COMMENT '重复周期：0、不重复；1、日；2、月；3、年；',
  `rate` int NOT NULL DEFAULT '1' COMMENT '重复频率',
  `remind_day` date DEFAULT NULL COMMENT '提醒日期',
  `remind_time` time DEFAULT NULL COMMENT '提醒时间',
  `days` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '重复星期/天',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '结束日期：0、永不结束；',
  `uniqued` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '定时任务唯一值',
  `last_time` timestamp NULL DEFAULT NULL COMMENT '上次提醒日期',
  `is_remind` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否提醒过0=无，1=有',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日程提醒表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_schedule_reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_schedule_reply` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业用户ID',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '关联日程ID',
  `reply_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联评论ID',
  `to_uid` int unsigned NOT NULL DEFAULT '0' COMMENT '回复指定人员ID',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '任务开始时间',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '任务结束时间',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '评论内容',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日程回复表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_schedule_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_schedule_task` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业用户ID',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '关联日程ID',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '任务开始时间',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '任务结束时间',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '日程状态：0、待定；1、接受；2、拒绝；3、完成；',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日程任务表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_schedule_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_schedule_type` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL,
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户UID',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `color` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '日程分类颜色',
  `info` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类简介',
  `is_public` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否公共分类',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`,`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日程分类表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_schedule_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_schedule_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业用户ID',
  `schedule_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联日程ID',
  `is_master` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为组织人0=否，1=是',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日程用户表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_storage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_storage` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `cid` bigint unsigned NOT NULL,
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `creater` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建用户ID',
  `name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '物资名称',
  `specs` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '物资规格',
  `factory` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '生产厂家',
  `units` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '计量单位',
  `mark` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `remark` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '重要信息',
  `stock` int unsigned NOT NULL DEFAULT '0' COMMENT '库存',
  `used` int NOT NULL DEFAULT '0' COMMENT '领用数量',
  `number` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '物资编号',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '物资类型：0、消耗物资；1、固定物资；',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '物资状态：0、正常；1、已领用；3、维修中；4、已报废；	',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联记录ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='物资表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_storage_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_storage_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '分类自增id',
  `pid` int NOT NULL DEFAULT '0' COMMENT '父级ID',
  `cate_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路径',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `level` int NOT NULL DEFAULT '0' COMMENT '等级',
  `type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '分类类型:0、消耗物资；1、固定物资；',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `m_type` (`type`,`entid`) USING BTREE,
  KEY `type_cate` (`id`,`type`,`level`) USING BTREE,
  KEY `eb_storage_category_pid_index` (`pid`) USING BTREE,
  KEY `eb_storage_category_type_index` (`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='物资分类表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_storage_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_storage_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `operator` int unsigned NOT NULL DEFAULT '0' COMMENT '操作用户id',
  `storage_id` bigint unsigned NOT NULL,
  `storage_type` tinyint unsigned DEFAULT '0' COMMENT '物资类型',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `creater` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '创建用户ID',
  `card_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID',
  `frame_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联组织架构ID',
  `info` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作说明',
  `mark` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注信息',
  `price` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '单价',
  `total` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '总价',
  `num` int unsigned NOT NULL DEFAULT '0' COMMENT '物资数量',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '操作类型：0、入库；1、领用；2、归还；3、维修；4、报废；5、维修完成；',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '当前物资状态',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_storage_record_storage_id_foreign` (`storage_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='物资操作记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_sub_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_sub_table` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `table_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '表名',
  `sub_table_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '目前表名',
  `num` int unsigned NOT NULL DEFAULT '0' COMMENT '目前表名增产',
  `count` int unsigned NOT NULL DEFAULT '0' COMMENT '当前表数据条数',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_sub_table_table_name_index` (`table_name`) USING BTREE,
  KEY `eb_sub_table_sub_table_name_index` (`sub_table_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分表管理表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_admin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `account` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员账号',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员头像',
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员密码',
  `real_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员姓名',
  `roles` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员权限(对应权限规则表主键)',
  `last_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访问ip',
  `login_count` int NOT NULL DEFAULT '0' COMMENT '管理员登陆次数',
  `level` tinyint NOT NULL DEFAULT '1' COMMENT '管理员级别',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '管理员状态 1有效0无效',
  `is_del` timestamp NULL DEFAULT NULL COMMENT '是否删除',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'TOKEN',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `status` (`account`,`status`,`is_del`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统管理员表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_attach`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_attach` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '附件ID',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '分后台ID',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上传用户uid',
  `name` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件名称',
  `real_name` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件原始名称',
  `att_dir` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件路径',
  `thumb_dir` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件压缩路径',
  `att_size` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件大小',
  `att_type` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '附件类型',
  `file_ext` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件扩展名',
  `cid` int unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `up_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '上传方式：1、本地；2、七牛云；3、OSS；4、COS。',
  `way` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '来源：1、总后台；2、分后台；3、用户。',
  `relation_type` tinyint NOT NULL DEFAULT '0' COMMENT '模块:1、汇报；',
  `relation_id` int NOT NULL DEFAULT '0' COMMENT '模块ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `entid` (`entid`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统附件表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_backup` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `path` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件路径',
  `uid` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '创建用户ID',
  `version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '版本号',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统备份表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_city`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_city` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `city_id` int NOT NULL DEFAULT '0' COMMENT '城市ID',
  `level` int NOT NULL DEFAULT '0' COMMENT '省市级别',
  `parent_id` int NOT NULL DEFAULT '0' COMMENT '父级id',
  `area_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '区号',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `merger_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '合并名称',
  `lng` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '经度',
  `lat` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '纬度',
  `is_show` tinyint NOT NULL DEFAULT '0' COMMENT '是否展示',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统城市表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `category` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '配置分类',
  `key` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置字段',
  `key_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名称',
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '类型(文本框,单选按钮...)',
  `input_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'input' COMMENT '表单类型',
  `cate_id` int NOT NULL DEFAULT '0' COMMENT '配置分类id',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置分类路径',
  `parameter` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则 单选框和多选框',
  `upload_type` tinyint NOT NULL DEFAULT '0' COMMENT '上传文件格式1单图2多图3文件',
  `required` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则',
  `width` int NOT NULL DEFAULT '0' COMMENT '多行文本框的宽度',
  `high` int NOT NULL DEFAULT '0' COMMENT '多行文框的高度',
  `value` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '默认值',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置简介',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `entid` int NOT NULL DEFAULT '0' COMMENT '0=总后台,1=分后台',
  `ent_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分后台',
  `is_show` tinyint NOT NULL DEFAULT '0' COMMENT '是否隐藏',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `ent_key` (`key`,`entid`) USING BTREE,
  KEY `key` (`key`,`cate_id`,`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `table_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表中文名',
  `table_name_en` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表英文名',
  `cate_ids` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类IDS',
  `info` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '说明',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '主表CRUD_ID；为空为主表',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '创建者ID',
  `form_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '当前form选择中的字段集合',
  `list_type` tinyint NOT NULL DEFAULT '0' COMMENT '0=默认；1=树形',
  `is_update_form` tinyint NOT NULL DEFAULT '0' COMMENT '是否允许修改表单',
  `is_update_table` tinyint NOT NULL DEFAULT '0' COMMENT '是否允许修改表格',
  `show_log` tinyint NOT NULL DEFAULT '0' COMMENT '是否展示日志',
  `comment_title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '评论标题',
  `show_comment` tinyint NOT NULL DEFAULT '0' COMMENT '是否展示评论',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_form_table` tinyint NOT NULL DEFAULT '0' COMMENT '是否存在表格',
  `table_field` json NOT NULL COMMENT '表格提交字段和展示字段',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_table_name_en_index` (`table_name_en`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_approve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_approve` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '关联CRUD_ID',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审批名称',
  `icon` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审批图标',
  `color` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审批图标颜色',
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '审批说明',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：0、关闭；1、开启；',
  `types` int unsigned NOT NULL DEFAULT '0' COMMENT '审批类型',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_approve_crud_id_index` (`crud_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD审批表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_approve_process`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_approve_process` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户名片ID',
  `approve_id` bigint unsigned NOT NULL COMMENT '关联流程ID',
  `level` int unsigned NOT NULL DEFAULT '1' COMMENT '流程级别',
  `groups` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '分组ID',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '节点名称',
  `types` tinyint unsigned NOT NULL COMMENT '节点类型：0、申请人；1、审批人；2、抄送人；3、条件；4、路由；',
  `uniqued` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '节点唯一值',
  `settype` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '审核人类型：1、指定成员；2、指定部门主管；7、连续多部门；5、申请人自己；4、申请人自选；(0、无此条件)',
  `director_order` tinyint NOT NULL DEFAULT '-1' COMMENT '指定层级顺序：0、从上至下；1、从下至上；(-1、无此条件)',
  `director_level` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '指定主管层级/指定终点层级：1-10；(0、无此条件)',
  `no_hander` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '当前部门无负责人时：1、上级部门负责人审批；2、为空时跳过；(0、无此条件)',
  `dep_head` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '指定部门负责人',
  `self_select` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否允许自选抄送人',
  `select_range` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '可选范围：1、不限范围；2、指定成员；(0、无此条件)',
  `user_list` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '指定的成员列表',
  `select_mode` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '选人方式：1、单选；2、多选；(0、无此条件)',
  `examine_mode` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '多人审批方式：1、或签；2、会签；3、依次审批；(0、无此条件)',
  `priority` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '条件优先级',
  `parent` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '节点父级唯一值',
  `is_child` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否存在子节点',
  `is_condition` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否存在条件',
  `condition_list` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '条件详情',
  `is_initial` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否为初始数据',
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '数据详情',
  `pass_ratio` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '通过比例(%) 0=关闭（使用原逻辑）',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `system_crud_approve_id_foreign` (`approve_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD审批流程表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_approve_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_approve_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `approve_id` int unsigned NOT NULL DEFAULT '0' COMMENT '审批申请表的主键id',
  `crud_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'crud的主键id',
  `data_id` int unsigned NOT NULL DEFAULT '0' COMMENT '实体表主键id',
  `event` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '触发动作：create、update、delete',
  `approve_event` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审批动作：revoke、撤销，reject、驳回;',
  `table_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'crud的表名',
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '实体表数据',
  `original_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '原来实体表数据',
  `original_schedule_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '原来实体附表数据',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_approve_record_approve_id_index` (`approve_id`) USING BTREE,
  KEY `eb_system_crud_approve_record_crud_id_index` (`crud_id`) USING BTREE,
  KEY `eb_system_crud_approve_record_data_id_index` (`data_id`) USING BTREE,
  KEY `eb_system_crud_approve_record_event_index` (`event`) USING BTREE,
  KEY `eb_system_crud_approve_record_approve_event_index` (`approve_event`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD审批记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_approve_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_approve_rule` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `approve_id` bigint unsigned NOT NULL,
  `range` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '可见范围',
  `abnormal` int unsigned NOT NULL DEFAULT '0' COMMENT '异常处理：0、自动同意；指定处理人ID；',
  `auto` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自动审批：0、首个节点处理，其他自动同意；1、连续审批自动同意；2、每个节点都需审批；',
  `edit` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '修改权限：0、员工不可修改固定人员；1、不可删除固定抄送人；',
  `recall` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '异常处理：1、审批通过后允许撤销；',
  `is_sign` int unsigned NOT NULL DEFAULT '0' COMMENT '是否可加签',
  `is_transfer` int unsigned NOT NULL DEFAULT '0' COMMENT '是否可转审',
  `refuse` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT '被拒绝后：0、返回初始，所有人重新审批；1、跳过已通过层级；',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `curd_approve_rule_id_foreign` (`approve_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD审批规则表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_cate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_cate` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `info` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '应用简介',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD分类表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_comment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `crud_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'crud的主键id',
  `data_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'crud的表的自增id',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '评论父级id',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '评论内容',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_comment_crud_id_data_id_index` (`crud_id`,`data_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD评论表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_curl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_curl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '接口标题',
  `is_pre` tinyint NOT NULL DEFAULT '0' COMMENT '0=直接请求，1=前置请求',
  `pre_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前置请求地址',
  `pre_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'post' COMMENT '前置请求method',
  `pre_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '前置请求header',
  `pre_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '前置请求data',
  `pre_cache_time` int NOT NULL DEFAULT '0' COMMENT '前置请求缓存时间',
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求地址',
  `method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'post' COMMENT '请求method',
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '请求header',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '请求data',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD接口表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_dashboard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_dashboard` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `update_user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '修改用户ID',
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `configure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '布局',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态：0、关闭；1、开启；',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD仪表盘表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_data_share`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_data_share` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `share_id` int unsigned NOT NULL DEFAULT '0' COMMENT '数据共享ID',
  `crud_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'crud的主键id',
  `data_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'crud的表的自增id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户表的id',
  `is_show` tinyint NOT NULL DEFAULT '0' COMMENT '可查看',
  `is_update` tinyint NOT NULL DEFAULT '0' COMMENT '可修改',
  `is_delete` tinyint NOT NULL DEFAULT '0' COMMENT '可删除',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_data_share_crud_id_data_id_user_id_index` (`crud_id`,`data_id`,`user_id`) USING BTREE,
  KEY `eb_system_crud_data_share_crud_id_user_id_index` (`crud_id`,`user_id`) USING BTREE,
  KEY `eb_system_crud_data_share_share_id_index` (`share_id`) USING BTREE,
  KEY `eb_system_crud_data_share_crud_id_index` (`crud_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD数据共享表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_event` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '关联CRUD_ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事件名称',
  `event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事件类型',
  `action` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '触发动作',
  `sort` int NOT NULL DEFAULT '0' COMMENT '优先级',
  `timer` int NOT NULL DEFAULT '0' COMMENT '定时任务执行周期',
  `timer_type` tinyint NOT NULL DEFAULT '0' COMMENT '周期类型:0=间隔秒数；1=间隔n分；2=间隔n小时；3=间隔n天；4=每天；5=每星期；6=每年',
  `target_crud_id` int NOT NULL DEFAULT '0' COMMENT '目标实体',
  `crud_approve_id` int NOT NULL DEFAULT '0' COMMENT '实体内的审核ID',
  `curl_id` int NOT NULL DEFAULT '0' COMMENT '接口管理id',
  `send_type` tinyint NOT NULL DEFAULT '0' COMMENT '发送用户类型:0=内部;1=外部',
  `send_user` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '发送用户',
  `notify_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通知类型',
  `additional_search` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '附加搜索视图信息',
  `additional_search_boolean` tinyint NOT NULL DEFAULT '0' COMMENT '附加搜索条件：0=符合其一 1= 符合全部',
  `template` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板内容',
  `field_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '字段信息',
  `aggregate_target_search` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '聚合目标搜索',
  `aggregate_target_search_boolean` tinyint NOT NULL DEFAULT '0' COMMENT '聚合目标搜索：0=符合其一 1= 符合全部',
  `aggregate_data_search` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '聚合数据搜索',
  `aggregate_data_search_boolean` tinyint NOT NULL DEFAULT '0' COMMENT '聚合数据搜索：0=符合其一 1= 符合全部',
  `aggregate_data_field` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分组字段关联',
  `aggregate_field_rule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '聚合字段规则',
  `sms_template_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '短信模板id',
  `work_webhook_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业微信bot webhook地址',
  `ding_webhook_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '钉钉机器人webhook地址',
  `other_webhook_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '其他bot webhook地址',
  `update_field_options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '更新字段相关数据',
  `other_webhook_status` tinyint NOT NULL DEFAULT '0' COMMENT '其他bot 状态',
  `ding_webhook_status` tinyint NOT NULL DEFAULT '0' COMMENT '钉钉机器人webhook状态',
  `work_webhook_status` tinyint NOT NULL DEFAULT '0' COMMENT '企业微信bot webhook状态',
  `sms_status` tinyint NOT NULL DEFAULT '0' COMMENT '短信状态',
  `system_status` tinyint NOT NULL DEFAULT '0' COMMENT '系统消息状态',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '其他信息',
  `timer_options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '执行周期配置详情',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0=关闭;1=开启',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_event_crud_id_index` (`crud_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD触发器表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_event_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_event_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '关联CRUD_ID',
  `event_id` int NOT NULL DEFAULT '0' COMMENT '触发器ID',
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '触发类型',
  `result` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '触发结果',
  `parameter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '出发参数',
  `log` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '日志内容',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_event_log_crud_id_event_id_index` (`crud_id`,`event_id`) USING BTREE,
  KEY `eb_system_crud_event_log_crud_id_index` (`crud_id`) USING BTREE,
  KEY `eb_system_crud_event_log_event_id_index` (`event_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD触发器日志表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_field` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '关联CRUD_ID',
  `field_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段中文名',
  `field_name_en` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段英文名',
  `form_value` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表单值类型',
  `field_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段类型',
  `is_default_value_not_null` tinyint NOT NULL DEFAULT '0' COMMENT '是否允许空值',
  `is_table_show_row` tinyint NOT NULL DEFAULT '0' COMMENT '是否在列表中默认显示',
  `comment` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段说明',
  `prev_field` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前一个字段英文名',
  `data_dict_id` int NOT NULL DEFAULT '0' COMMENT '数据字典ID',
  `association_crud_id` int NOT NULL DEFAULT '0' COMMENT '一对一关联CRUD_ID',
  `is_main` tinyint NOT NULL DEFAULT '0' COMMENT '主展示字段',
  `is_form` tinyint NOT NULL DEFAULT '0' COMMENT '是否展示在表单中',
  `form_field_uniqid` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表单字段唯一值',
  `association_field_names` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '一对一关联字段展示',
  `options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '其他表单信息',
  `create_modify` tinyint NOT NULL DEFAULT '1' COMMENT '是否创建时可以修改',
  `update_modify` tinyint NOT NULL DEFAULT '1' COMMENT '是否修改时可以修改',
  `is_default` tinyint NOT NULL DEFAULT '0' COMMENT '是否默认字段',
  `data_type` tinyint NOT NULL DEFAULT '0' COMMENT '0=数据字典；1=静态数据；3=数据接口',
  `is_uniqid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=不唯一，1=唯一',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `customize_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '自定义json数据',
  `association_show_type` tinyint NOT NULL DEFAULT '1' COMMENT '0=下拉，1=弹窗',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_field_crud_id_index` (`crud_id`) USING BTREE,
  KEY `eb_system_crud_field_field_name_en_index` (`field_name_en`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD字段表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_form` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '关联CRUD_ID',
  `version` int NOT NULL DEFAULT '0' COMMENT '版本号',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单信息',
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单字段信息',
  `global_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单公共信息',
  `is_index` tinyint NOT NULL DEFAULT '0' COMMENT '是否主表单',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单名称',
  `is_master` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否主要的表单',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_form_crud_id_index` (`crud_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD表单表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `crud_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'crud的主键id',
  `data_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'crud的表的自增id',
  `log_type` enum('create','update','delete','share_create','share_delete','share_update','transfer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'create' COMMENT '状态：create=创建；update=更新；',
  `change_field_name_en` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '修改的字段名称，可以为空',
  `before_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '修改之前的值',
  `after_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '修改之后的值',
  `data_crud_id` int unsigned NOT NULL DEFAULT '0' COMMENT '数据的crud的主键id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_log_crud_id_data_id_index` (`crud_id`,`data_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD日志表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_operate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_operate` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `crud_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'crud的主键id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作名称',
  `operate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作唯一值',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `system_crud_form_id` int NOT NULL DEFAULT '0' COMMENT '选择的表单ID',
  `operate_type` tinyint NOT NULL DEFAULT '0' COMMENT '0=列表头部，1=列表中',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态',
  `action_type` tinyint NOT NULL DEFAULT '0' COMMENT '0=新增，1=编辑',
  `popup_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '弹窗标题',
  `use_rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '启用规则',
  `options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '参数设置',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `eb_system_crud_operate_operate_unique` (`operate`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD操作表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_questionnaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_questionnaire` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '问卷调查地址',
  `unique` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '唯一值',
  `crud_id` int unsigned NOT NULL DEFAULT '0' COMMENT '实体的id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人的id',
  `role_type` tinyint NOT NULL DEFAULT '0' COMMENT '0=仅企业员工可见，1=所有人',
  `invalid_time` datetime NOT NULL COMMENT '失效时间',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '0=关闭；1=开启',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `eb_system_crud_questionnaire_unique_unique` (`unique`) USING BTREE,
  KEY `eb_system_crud_questionnaire_crud_id_index` (`crud_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问卷调查';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联角色ID',
  `crud_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联实体ID',
  `crud_name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '关联实体名称',
  `created` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '新增权限',
  `reade` int unsigned NOT NULL DEFAULT '0' COMMENT '查看权限:4、全部.3、指定部门.2、当前部门.1、仅本人.0、不允许',
  `reade_frame` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '查看部门',
  `updated` int unsigned NOT NULL DEFAULT '0' COMMENT '修改权限:4、全部.3、指定部门.2、当前部门.1、仅本人.0、不允许',
  `updated_frame` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '可修改部门',
  `deleted` int unsigned NOT NULL DEFAULT '0' COMMENT '删除权限:4、全部.3、指定部门.2、当前部门.1、仅本人.0、不允许',
  `deleted_frame` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '可删除部门',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `transfer` int unsigned NOT NULL DEFAULT '0' COMMENT '查看权限:4、全部.3、指定部门.2、当前部门.1、仅本人.0、不允许',
  `transfer_frame` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '查看部门',
  `share` int unsigned NOT NULL DEFAULT '0' COMMENT '查看权限:4、全部.3、指定部门.2、当前部门.1、仅本人.0、不允许',
  `share_frame` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '查看部门',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD角色表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_senior_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_senior_search` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '关联CRUD_ID',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '关联USER_ID',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `senior_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '高级搜索标题',
  `senior_search` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '高级搜索',
  `senior_type` tinyint NOT NULL DEFAULT '0' COMMENT '0=个人，1=系统',
  `search_boolean` tinyint NOT NULL DEFAULT '0' COMMENT '0=or，1=and',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_senior_search_crud_id_index` (`crud_id`) USING BTREE,
  KEY `eb_system_crud_senior_search_user_id_index` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD高级搜索表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_share`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_share` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `crud_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'crud的主键id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户表的id',
  `role_type` tinyint NOT NULL DEFAULT '0' COMMENT '0=查看，1=可查看，可编辑，2=可查看，可编辑，可删除',
  `operate_user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人的id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_share_crud_id_user_id_index` (`crud_id`,`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD分享表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_table` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '关联CRUD_ID',
  `version` int NOT NULL DEFAULT '0' COMMENT '版本号',
  `senior_search` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '高级搜索',
  `view_search` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '视图搜索',
  `show_field` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '默认展示字段搜索',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '其他表单信息',
  `is_index` tinyint NOT NULL DEFAULT '0' COMMENT '是否主配置',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_table_crud_id_index` (`crud_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD table设置表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_crud_table_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_crud_table_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '关联CRUD_ID',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '关联USER_ID',
  `senior_search` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '高级搜索',
  `show_field` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '字段信息',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '其他信息',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_crud_table_user_crud_id_user_id_index` (`crud_id`,`user_id`) USING BTREE,
  KEY `eb_system_crud_table_user_crud_id_index` (`crud_id`) USING BTREE,
  KEY `eb_system_crud_table_user_user_id_index` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统CRUD 列表用户设置表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_group` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `cate_id` int NOT NULL DEFAULT '0' COMMENT '分类id',
  `group_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '数据字段英文名',
  `group_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '数据字段中文名称',
  `group_info` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '数据字段提示',
  `fields` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '数据组字段以及类型（json数据）',
  `entid` int NOT NULL DEFAULT '0' COMMENT '商家ID：0、总平台',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_group_cate_id_group_key_index` (`cate_id`,`group_key`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统组合数据表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_group_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_group_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '组合数据自增id',
  `group_id` int NOT NULL DEFAULT '0' COMMENT '组合数据数组ID(关联system_group表id)',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '数据组对应的数据值（json数据）',
  `sort` int NOT NULL DEFAULT '0' COMMENT '数据排序',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态 1=开启,0=关闭',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_group_data_group_id_index` (`group_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统组合数据数据表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '日志自增id',
  `admin_id` int NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `admin_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员姓名',
  `path` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链接',
  `method` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访问方式',
  `event_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '行为',
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '类型',
  `terminal` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访问终端',
  `last_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '访问ip',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_system_log_admin_id_index` (`admin_id`) USING BTREE,
  KEY `eb_system_log_admin_name_index` (`admin_name`) USING BTREE,
  KEY `eb_system_log_type_index` (`type`) USING BTREE,
  KEY `eb_system_log_terminal_index` (`terminal`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统用户操作日志表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单自增id',
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级菜单ID',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '按钮图标',
  `menu_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '按钮名',
  `api` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'api请求地址',
  `methods` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求方式POST GET PUT DELETE',
  `unique_auth` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前台唯一标识',
  `menu_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前端路由路径',
  `menu_type` int NOT NULL DEFAULT '0' COMMENT '路由类型：0、系统；1、实体；',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '实体id',
  `uni_path` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '移动端路径',
  `uni_img` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '移动端图标',
  `position` tinyint DEFAULT '0' COMMENT '菜单位置 0=侧方1=顶部',
  `paths` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路径',
  `component` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前端路径',
  `level` tinyint unsigned NOT NULL DEFAULT '0',
  `other` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '其他参数',
  `sort` int NOT NULL DEFAULT '1' COMMENT '排序',
  `entid` int NOT NULL DEFAULT '0' COMMENT '菜单归属 0=总后台',
  `type` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'D' COMMENT '类型：M、菜单；B、按钮；A、接口；',
  `is_show` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否为隐藏菜单供前台使用',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '菜单状态 1=开启,0=关闭',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `uniqued` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '菜单唯一标识',
  `parent_uniqued` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '父菜单唯一标识',
  `crud_app_id` int NOT NULL DEFAULT '0' COMMENT '低代码的应用id',
  `crud_dashboard_id` int NOT NULL DEFAULT '0' COMMENT '低代码图表的id',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `idx_uniqued` (`uniqued`) USING BTREE,
  KEY `is_admin` (`status`,`entid`) USING BTREE,
  KEY `api` (`api`) USING BTREE,
  KEY `type` (`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统菜单表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_menus_copy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_menus_copy` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单自增id',
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级菜单ID',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '按钮图标',
  `menu_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '按钮名',
  `api` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'api请求地址',
  `methods` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求方式POST GET PUT DELETE',
  `unique_auth` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前台唯一标识',
  `menu_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前端路由路径',
  `menu_type` int NOT NULL DEFAULT '0' COMMENT '路由类型：0、系统；1、实体；',
  `crud_id` int NOT NULL DEFAULT '0' COMMENT '实体id',
  `uni_path` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '移动端路径',
  `uni_img` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '移动端图标',
  `position` tinyint DEFAULT '0' COMMENT '菜单位置 0=侧方1=顶部',
  `paths` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路径',
  `component` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前端路径',
  `level` tinyint unsigned NOT NULL DEFAULT '0',
  `other` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '其他参数',
  `sort` int NOT NULL DEFAULT '1' COMMENT '排序',
  `uniqued` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '菜单唯一标识',
  `parent_uniqued` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '父菜单唯一标识',
  `entid` int NOT NULL DEFAULT '0' COMMENT '菜单归属 0=总后台',
  `type` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'D' COMMENT '类型：M、菜单；B、按钮；A、接口；',
  `is_show` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否为隐藏菜单供前台使用',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '菜单状态 1=开启,0=关闭',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `idx_uniqued` (`uniqued`) USING BTREE,
  KEY `is_admin` (`status`,`entid`) USING BTREE,
  KEY `api` (`api`) USING BTREE,
  KEY `type` (`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统菜单表copy';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_package`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_package` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '扩展包名称',
  `info` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '扩展包简介',
  `version` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '扩展包版本',
  `file` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '扩展包文件名',
  `path` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '扩展包路径',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否可用',
  `uniqued` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '校验码',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统扩展包表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_paytype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_paytype` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付方式名称',
  `ident` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付方式标识',
  `info` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '简介',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '是否可用：1、是；0、否；',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付方式表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_quick`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_quick` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题名称',
  `cid` int unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `pc_url` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'PC端地址',
  `uni_url` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '移动端地址',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序，数字越大越在前面',
  `types` tinyint NOT NULL DEFAULT '1' COMMENT '菜单类型 0:个人菜单 1:企业菜单',
  `pc_show` tinyint NOT NULL DEFAULT '0' COMMENT 'PC端显示 0:隐藏 1:显示',
  `uni_show` tinyint NOT NULL DEFAULT '0' COMMENT '移动端显示 0:隐藏 1:显示',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态 0:隐藏 1:显示',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='快捷入口表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '权限自增id',
  `role_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份管理名称',
  `rules` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '身份管理权限(system_menus主键ID)',
  `apis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '身份管理接口权限(system_menus主键ID)',
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '超级角色类型,空表示总后台',
  `entid` int NOT NULL DEFAULT '0' COMMENT '0=总后台,非0为企业后台',
  `level` tinyint NOT NULL DEFAULT '0' COMMENT '等级',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态',
  `uniqued` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '企业唯一值',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统角色表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_system_storage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_system_storage` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `access_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'access_key',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=本地存储,2=七牛,3=oss,4=cos',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '空间名',
  `region` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '地域',
  `acl` enum('private','public-read','public-read-write') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public-read' COMMENT '权限',
  `domain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '空间域名',
  `cdn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'CDN加速域名',
  `cname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'CNAME值',
  `is_ssl` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=http,1=https',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `add_time` int NOT NULL COMMENT '添加事件',
  `update_time` int NOT NULL COMMENT '更新事件',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `access_key` (`access_key`,`type`,`is_delete`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `is_delete` (`is_delete`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='云储存';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_task` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业id，0=全局任务',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '任务名称',
  `period` enum('year','month','week','day','second','once') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'second' COMMENT '任务执行类型',
  `persist` tinyint NOT NULL DEFAULT '0' COMMENT '是否永久执行',
  `run_count` int NOT NULL DEFAULT '1' COMMENT '执行次数最少1次',
  `exe_count` int NOT NULL DEFAULT '0' COMMENT '已经执行次数',
  `class_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '执行任务类名',
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '执行任务方法名',
  `interval` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '执行时间,一般为json格式',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `rate` int unsigned NOT NULL DEFAULT '0' COMMENT '间隔时间：n天、n月、n年、n周',
  `parameter` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '执行参数一般为json格式',
  `uniqued` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '任务唯一值',
  `delete` timestamp NULL DEFAULT NULL COMMENT '是否删除',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_task_run_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_task_run_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `task_id` bigint unsigned NOT NULL,
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '错误提示',
  `line` int NOT NULL DEFAULT '0' COMMENT '错误行数',
  `files` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '错误文件',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '1=执行成功;0=执行失败;-1=未执行',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务执行记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_todo_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_todo_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '待办归属用户ID',
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '待办类型(对应TodoEnum)',
  `source_id` int unsigned NOT NULL DEFAULT '0' COMMENT '来源表主键ID',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '待办标题',
  `extra` json DEFAULT NULL COMMENT '类型特有扩展数据',
  `source_created_at` datetime(3) DEFAULT NULL COMMENT '来源记录的created_at(排序依据)',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=待办中 2=已完成/已失效',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_type_source` (`user_id`,`type`,`source_id`),
  KEY `idx_user_status_created` (`user_id`,`status`,`source_created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='统一待办表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user` (
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户uid',
  `account` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户账号',
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户密码',
  `only_pwd` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户密码',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户头像',
  `real_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户真实姓名',
  `education` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学历',
  `nation` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '汉' COMMENT '民族',
  `birthplace` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '籍贯',
  `card_id` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证号码',
  `province` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '现住所在省',
  `city` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '现住所在城市',
  `area` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '现住所在区',
  `current_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '现住地',
  `home_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '家庭住址',
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电话',
  `phone` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `standby_contacts` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备用联系人名',
  `standby_contacts_phone` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备用联系人手机号',
  `bank` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行',
  `bank_number` varchar(21) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '银行卡号',
  `age` int NOT NULL DEFAULT '0' COMMENT '年龄',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '当前登陆所在企业ID',
  `last_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '访问ip',
  `uni_online` tinyint(1) NOT NULL DEFAULT '0' COMMENT '移动端登录状态',
  `client_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '连接通道ID',
  `scan_key` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '扫码登录参数',
  `birthday` timestamp NULL DEFAULT NULL COMMENT '生日',
  `login_count` int NOT NULL DEFAULT '0' COMMENT '登陆次数',
  `marriage` tinyint NOT NULL DEFAULT '0' COMMENT '是否结婚 0 = 未结婚,1=结婚',
  `sex` tinyint NOT NULL DEFAULT '0' COMMENT '性别 0=未知,1=男,2=女,3=其他',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：0、锁定；1、正常；',
  `is_init` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否为初始密码',
  `language` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `delete` timestamp NULL DEFAULT NULL COMMENT '是否删除',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`uid`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE,
  KEY `eb_user_account_index` (`account`) USING BTREE,
  KEY `eb_user_real_name_index` (`real_name`) USING BTREE,
  KEY `eb_user_phone_index` (`phone`) USING BTREE,
  KEY `eb_user_entid_index` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户信息表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_assess`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_assess` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `period` tinyint NOT NULL DEFAULT '0' COMMENT '周期:1=周;2=月;3=年',
  `planid` bigint unsigned NOT NULL,
  `frame_id` int NOT NULL DEFAULT '0' COMMENT '组织架构ID',
  `number` int NOT NULL DEFAULT '0' COMMENT '考核批次ID',
  `check_uid` int NOT NULL DEFAULT '0' COMMENT '考核用户信息表ID',
  `test_uid` int NOT NULL DEFAULT '0' COMMENT '被考核用户信息表ID',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '考核开始时间',
  `make_time` timestamp NULL DEFAULT NULL COMMENT '目标制定时间结束时间',
  `make_status` tinyint NOT NULL DEFAULT '0' COMMENT '目标制定状态：0、未制定；1、已启用；2、草稿。',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '考核结束时间',
  `test_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '自评状态：0、未评价；1、已评价；2、草稿；',
  `check_end` timestamp NULL DEFAULT NULL COMMENT '上级评价结束时间',
  `check_status` tinyint NOT NULL DEFAULT '0' COMMENT '上级评价状态：0、未评价；1、已评价；2、草稿。',
  `verify_time` timestamp NULL DEFAULT NULL COMMENT '审核结束时间',
  `verify_status` tinyint NOT NULL DEFAULT '0' COMMENT '审核状态：0、未审核；1、已审核；',
  `score` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '考核得分',
  `total` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '最高分',
  `grade` int NOT NULL DEFAULT '0' COMMENT '考核等级',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '考核状态：0、目标制定；1、自评期；2、上级评价；3、审核期；4、结束；',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '评分方式：0、加权评分；1、加和评分',
  `intact` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '完整性：1、是；0、否',
  `is_show` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否启用：0、未启用；1、已启用；',
  `delete` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户考核表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_card_perfect`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_card_perfect` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `creator` int unsigned NOT NULL DEFAULT '0' COMMENT '邀请人ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '被邀请人ID',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
  `uid` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联用户UID',
  `card_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联企业用户名片ID',
  `uniqued` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '唯一值',
  `total` int NOT NULL DEFAULT '0' COMMENT '可操作量：-1、不限',
  `used` int unsigned NOT NULL DEFAULT '0' COMMENT '已使用量',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态：0、待处理；1、已通过；2、已拒绝；',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否绑定用户信息',
  `fail_time` datetime DEFAULT NULL COMMENT '失效时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户名片完善邀请表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_change`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_change` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '用户信息变动自增id',
  `uuid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户uuid(关联user表uuid)',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '变动类型',
  `change_mesage` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '变动说明',
  `change_time` timestamp NULL DEFAULT NULL COMMENT '变动时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户信息变动表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_education_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_education_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联用户ID',
  `resume_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联简历ID',
  `start_time` date DEFAULT NULL COMMENT '开始时间',
  `end_time` date DEFAULT NULL COMMENT '结束时间',
  `school_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学校名称',
  `major` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所学专业',
  `education` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学历',
  `academic` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学位',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_user_education_history_uid_index` (`uid`) USING BTREE,
  KEY `eb_user_education_history_resume_id_index` (`resume_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户教育经历表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_enterprise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_enterprise` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entid` bigint unsigned NOT NULL,
  `ident` int NOT NULL DEFAULT '0' COMMENT '用户在企业中身份：-1、创建人；',
  `card_id` int NOT NULL DEFAULT '0' COMMENT '企业名片ID',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像',
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `job` int unsigned NOT NULL DEFAULT '0' COMMENT '职位ID',
  `roles` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '角色权限',
  `verify` tinyint NOT NULL DEFAULT '0' COMMENT '0=待审核,1=审核通过',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '用户在企业中状态：0、锁定；1、正常',
  `join_time` date DEFAULT NULL COMMENT '加入时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `ent_ident` (`entid`,`ident`) USING BTREE,
  KEY `u_statu` (`uid`,`entid`) USING BTREE,
  KEY `uid_ent` (`uid`,`entid`) USING BTREE,
  KEY `entid` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户企业表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_enterprise_apply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_enterprise_apply` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int NOT NULL DEFAULT '0' COMMENT '发送人或者企业ID',
  `send_uid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '送达人id或者企业',
  `frame_id` int NOT NULL,
  `status` int NOT NULL DEFAULT '0' COMMENT '-1=待处理,0=拒绝;1=同意',
  `verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核状态：0、待审核；1、已通过；-1、拒绝；',
  `perfect_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '邀请完善信息记录关联',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '申请时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户企业申请表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_enterprise_invite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_enterprise_invite` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `send_uid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '生成邀请码用户uuid',
  `frame_id` int NOT NULL DEFAULT '0' COMMENT '组织架构ID',
  `is_verify` tinyint NOT NULL DEFAULT '0' COMMENT '是否需要企业审核：1、是；0、否；',
  `uniqued` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链接唯一值',
  `perfect_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '邀请完善信息记录标识',
  `fail_time` timestamp NULL DEFAULT NULL COMMENT '失效时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户企业邀请表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_memorial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_memorial` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户ID',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题名称',
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容',
  `pid` int NOT NULL DEFAULT '0' COMMENT '分类ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户纪念日表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_memorial_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_memorial_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户ID',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路径',
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级ID',
  `types` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '类型：0、默认；1、用户添加',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户纪念日分类表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_pending`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_pending` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entid` bigint unsigned NOT NULL,
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '待办类型:1=绩效;2=日报',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '跳转路径',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '待办内容',
  `pend_ent_time` timestamp NULL DEFAULT NULL COMMENT '待办事件结束时间',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:1=已处理;0=未处理',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_user_pending_uid_foreign` (`uid`) USING BTREE,
  KEY `eb_user_pending_entid_foreign` (`entid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户待办表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_quick`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_quick` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `entid` int unsigned NOT NULL DEFAULT '0' COMMENT '企业id',
  `uuid` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '用户uid',
  `pc_menu_id` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'pc端菜单Id',
  `app_menu_id` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'app端菜单Id',
  `statistics_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '统计类型',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户快捷入口';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_remind_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_remind_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `week` int NOT NULL DEFAULT '0' COMMENT '当年的第几周',
  `month` int NOT NULL DEFAULT '0' COMMENT '当年的第几月',
  `day` int NOT NULL DEFAULT '0' COMMENT '当月的第几天',
  `year` int NOT NULL DEFAULT '0' COMMENT '那一年',
  `quarter` int NOT NULL DEFAULT '0' COMMENT '第几季度',
  `remind_type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提醒类型',
  `user_id` int NOT NULL DEFAULT '0' COMMENT 'admin表ID',
  `relation_id` int NOT NULL DEFAULT '0' COMMENT '关联id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_user_remind_log_year_entid_user_id_remind_type_index` (`year`,`entid`,`user_id`,`remind_type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户提醒日志表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_resume`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_resume` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `uid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '照片',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '员工姓名',
  `phone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `position` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '期望职位',
  `birthday` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '生日',
  `nation` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '种族',
  `politic` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '政治面貌',
  `native` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '籍贯',
  `address` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '居住地',
  `sex` tinyint NOT NULL DEFAULT '0' COMMENT '性别: 0、未知；1、男；2、女；',
  `age` tinyint unsigned NOT NULL DEFAULT '18' COMMENT '年龄',
  `marriage` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '婚姻状况:0、未婚；1、已婚；',
  `is_part` tinyint NOT NULL DEFAULT '0' COMMENT '是否兼职:1、是；0、否；',
  `work_years` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '工作年限',
  `spare_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '紧急联系人',
  `spare_tel` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '紧急联系电话',
  `email` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `work_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '入职时间',
  `trial_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '试用时间',
  `formal_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '转正时间',
  `treaty_time` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '合同到期时间',
  `social_num` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '社保账户',
  `fund_num` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '公积金账户',
  `bank_num` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '银行卡账户',
  `bank_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行',
  `graduate_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '毕业院校',
  `graduate_date` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '毕业时间',
  `card_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证号',
  `card_front` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证正面',
  `card_both` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证背面',
  `education` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学历',
  `education_image` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学历证书',
  `acad` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学位',
  `acad_image` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '学位证书',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_user_resume_uid_foreign` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户简历表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_schedule` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户ID',
  `entid` int NOT NULL DEFAULT '0' COMMENT '企业ID',
  `types` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '类型：user、用户；assess、考核；',
  `content` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '待办内容',
  `mark` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注信息',
  `remind` tinyint NOT NULL DEFAULT '1' COMMENT '是否提醒：1、是；0、否；',
  `repeat` tinyint NOT NULL DEFAULT '0' COMMENT '是否重复：1、是；0、否；',
  `period` tinyint NOT NULL DEFAULT '0' COMMENT '重复周期：0、天；1、周；2、月；3、年',
  `rate` int NOT NULL DEFAULT '1' COMMENT '重复频率',
  `remind_day` date DEFAULT NULL COMMENT '提醒日期',
  `remind_time` time DEFAULT NULL COMMENT '提醒时间',
  `days` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '重复星期/天',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '结束日期：0、用不结束；',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联ID',
  `uniqued` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '定时任务唯一值',
  `last_time` timestamp NULL DEFAULT NULL COMMENT '上次提醒日期',
  `is_remind` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否提醒过0=无，1=有',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户日程表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_schedule_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_schedule_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户ID',
  `schedultid` int NOT NULL DEFAULT '0' COMMENT '提醒ID',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '完成状态：1、是；0、否；',
  `remind_day` date DEFAULT NULL COMMENT '提醒日期',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户日程记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_token` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `uid` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联用户UID',
  `client` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登陆客户端名称',
  `last_ip` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登陆IP',
  `mac` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登陆MAC地址',
  `last_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '上次过期token',
  `remember_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '当前登陆token',
  `refresh_token_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '刷新TOKEN哈希',
  `refresh_expires_at` datetime DEFAULT NULL COMMENT '刷新TOKEN失效时间',
  `refresh_last_used_at` datetime DEFAULT NULL COMMENT '刷新TOKEN最后使用时间',
  `refresh_revoked_at` datetime DEFAULT NULL COMMENT '刷新TOKEN撤销时间',
  `fail_time` datetime DEFAULT NULL COMMENT '失效时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_user_token_refresh_hash` (`refresh_token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户登陆token表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_user_work_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_user_work_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联用户ID',
  `resume_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联简历ID',
  `start_time` date DEFAULT NULL COMMENT '开始时间',
  `end_time` date DEFAULT NULL COMMENT '结束时间',
  `company` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所在公司',
  `position` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '职位',
  `describe` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '工作描述',
  `quit_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '离职原因',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_user_work_history_uid_index` (`uid`) USING BTREE,
  KEY `eb_user_work_history_resume_id_index` (`resume_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户工作经历表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_view_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_view_search` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID',
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '视图名称',
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '视图内容',
  `category` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '视图分类(参考枚举类目)',
  `types` int unsigned NOT NULL DEFAULT '0' COMMENT '视图类型：0-系统 1-个人',
  `is_public` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否公开：0-否 1-是',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_view_search_uid_index` (`uid`) USING BTREE,
  KEY `eb_view_search_types_index` (`types`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='视图搜索表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_client` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业微信id',
  `external_userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外部联系人的userid',
  `uid` int NOT NULL DEFAULT '0' COMMENT '商城用户uid',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外部联系人的名称',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外部联系人头像',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '1表示该外部联系人是微信用户，2表示该外部联系人是企业微信用户',
  `gender` tinyint NOT NULL DEFAULT '0' COMMENT '性别 0-未知 1-男性 2-女性',
  `unionid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开放平台的唯一身份标识',
  `position` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外部联系人的职位',
  `corp_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外部联系人所在企业的简称',
  `corp_full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外部联系人所在企业的主体名称',
  `external_profile` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '外部联系人的详情',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注信息',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `userid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '添加了此外部联系人的企业成员userid',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业微信客户';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_client_follow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_client_follow` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL DEFAULT '0' COMMENT '客户id',
  `userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '添加了此外部联系人的企业成员userid',
  `remark` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '该成员对此外部联系人的备注',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '该成员对此外部联系人的描述',
  `createtime` int NOT NULL DEFAULT '0' COMMENT '该成员添加此外部联系人的时间',
  `remark_corp_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '该成员对此微信客户备注的企业名称',
  `remark_mobiles` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '该成员对此客户备注的手机号码',
  `add_way` tinyint NOT NULL DEFAULT '0' COMMENT '该成员添加此客户的来源',
  `oper_userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发起添加的userid，如果成员主动添加，为成员的userid；如果是客户主动添加，则为客户的外部联系人userid；如果是内部成员共享/管理员分配，则为对应的成员/管理员userid',
  `state` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自定义字段返回数据',
  `is_del_user` tinyint NOT NULL DEFAULT '0' COMMENT '客户是否删除跟踪人:0=没有,1=删除',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业微信客服跟踪';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_client_follow_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_client_follow_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `follow_id` int unsigned NOT NULL DEFAULT '0' COMMENT '跟踪人id',
  `group_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '该成员添加此外部联系人所打标签的分组名称',
  `tag_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '该成员添加此外部联系人所打标签名称',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '1-企业设置，2-用户自定义，3-规则组标签',
  `tag_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '该成员添加此外部联系人所打企业标签的id，用户自定义类型标签（type=2）不返回',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业微信客户跟踪标签';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_department` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业id',
  `department_id` int NOT NULL DEFAULT '0' COMMENT '部门id',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '部门名称',
  `name_en` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '部门英文名称',
  `department_leader` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'leader',
  `parentid` int NOT NULL DEFAULT '0' COMMENT '上级id',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业微信组织架构';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_group_chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_group_chat` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业ID',
  `chat_id` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户群ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '群名',
  `owner` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '群主ID',
  `group_create_time` int NOT NULL DEFAULT '0' COMMENT '群的创建时间',
  `notice` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '群公告',
  `admin_list` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '群管理员userid',
  `member_num` int NOT NULL DEFAULT '0' COMMENT '群人数',
  `retreat_group_num` int NOT NULL DEFAULT '0' COMMENT '退群总数',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '客户群跟进状态。\\r\\n0 - 跟进人正常\\r\\n1 - 跟进人离职\\r\\n2 - 离职继承中\\r\\n3 - 离职继承完成',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业微信群';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_group_chat_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_group_chat_member` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL DEFAULT '0' COMMENT '企业群ID',
  `userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '群成员id',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '成员类型。1 - 企业成员2 - 外部联系人',
  `unionid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '微信开放平台的唯一身份标识（微信unionid）',
  `join_time` int NOT NULL DEFAULT '0' COMMENT '入群时间',
  `join_scene` tinyint NOT NULL DEFAULT '0' COMMENT '入群方式。1 - 由群成员邀请入群（直接邀请入群）2 - 由群成员邀请入群（通过邀请链接入群）3 - 通过扫描群二维码入群',
  `invitor_userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邀请者。目前仅当是由本企业内部成员邀请入群时会返回该值',
  `group_nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '在群里的昵称',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名字。仅当 need_name = 1 时返回',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1=在群中,0=已退群',
  `chat_sum` int NOT NULL DEFAULT '0' COMMENT '当前群人数',
  `retreat_chat_num` int NOT NULL DEFAULT '0' COMMENT '当前退群人数',
  `state` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户群当前群成员的状态，0 - 已加入，1 - 已退出',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业微信群成员列表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_channel_code_cate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_channel_code_cate` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父级分组ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组名称',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_channel_code_cate_pid_index` (`pid`) USING BTREE,
  KEY `eb_work_channel_code_cate_uid_index` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企微渠道活码分组表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_channel_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_channel_code` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `group_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '活码分组ID',
  `corp_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业微信企业ID',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '活码名称',
  `userids` json DEFAULT NULL COMMENT '前端使用成员admin ID列表',
  `work_userids` json DEFAULT NULL COMMENT '企微使用成员userid列表',
  `tag_ids` json DEFAULT NULL COMMENT '前端客户标签ID列表',
  `work_tag_ids` json DEFAULT NULL COMMENT '自动打企微标签ID列表',
  `welcome_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '入群引导语',
  `welcome_messages` json DEFAULT NULL COMMENT '欢迎语消息配置',
  `invite_group` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否邀请加群:0否,1是',
  `code_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '邀请加群配置ID(work_channel_code_group.id)',
  `contact_config_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系我配置ID',
  `qr_code` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '联系我二维码',
  `skip_verify` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否免验证添加',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态:0禁用,1启用',
  `remark` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_channel_code_uid_index` (`uid`) USING BTREE,
  KEY `eb_work_channel_code_group_id_index` (`group_id`) USING BTREE,
  KEY `eb_work_channel_code_corp_id_index` (`corp_id`) USING BTREE,
  KEY `eb_work_channel_code_invite_group_index` (`invite_group`) USING BTREE,
  KEY `eb_work_channel_code_code_id_index` (`code_id`) USING BTREE,
  KEY `eb_work_channel_code_contact_config_id_index` (`contact_config_id`) USING BTREE,
  KEY `eb_work_channel_code_status_index` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企微渠道活码表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_channel_code_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_channel_code_group` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `chat_ids` json DEFAULT NULL COMMENT '客户群ID数组',
  `group_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户群名称',
  `join_way_config_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户群进群方式配置ID',
  `state` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '企微客户群进群方式state',
  `qr_code` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '进群二维码',
  `scene` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '企微进群方式场景:1群的小程序插件,2群二维码',
  `join_way_remark` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企微进群方式备注',
  `room_base_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自动建群群名前缀',
  `room_base_id` int unsigned NOT NULL DEFAULT '1' COMMENT '自动建群起始序号',
  `auto_create_room` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否群满自动建群',
  `max_member_num` smallint unsigned NOT NULL DEFAULT '200' COMMENT '本系统切群人数上限',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态:0禁用,1启用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `eb_work_channel_code_group_state_unique` (`state`) USING BTREE,
  KEY `eb_work_channel_code_group_join_way_config_id_index` (`join_way_config_id`) USING BTREE,
  KEY `eb_work_channel_code_group_sort_index` (`sort`) USING BTREE,
  KEY `eb_work_channel_code_group_status_index` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企微渠道活码客户群配置表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_channel_code_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_channel_code_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `channel_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '渠道活码ID',
  `group_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '渠道群配置ID',
  `corp_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业微信企业ID',
  `userid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '添加的成员userid',
  `external_userid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外部联系人userid',
  `welcome_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '欢迎语发送凭证',
  `state` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '扫码state',
  `send_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '发送状态:0待发送,1成功,2失败',
  `group_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '入群状态:0未知,1已发送,2已入群,3已退群',
  `customer_status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '客户状态:1已添加,2已流失',
  `error_msg` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '错误信息',
  `joined_at` timestamp NULL DEFAULT NULL COMMENT '客户入群时间',
  `left_at` timestamp NULL DEFAULT NULL COMMENT '客户退群时间',
  `lost_at` timestamp NULL DEFAULT NULL COMMENT '客户流失时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_channel_code_log_channel_id_index` (`channel_id`) USING BTREE,
  KEY `eb_work_channel_code_log_group_id_index` (`group_id`) USING BTREE,
  KEY `eb_work_channel_code_log_corp_id_index` (`corp_id`) USING BTREE,
  KEY `eb_work_channel_code_log_userid_index` (`userid`) USING BTREE,
  KEY `eb_work_channel_code_log_external_userid_index` (`external_userid`) USING BTREE,
  KEY `eb_work_channel_code_log_state_index` (`state`) USING BTREE,
  KEY `eb_work_channel_code_log_send_status_index` (`send_status`) USING BTREE,
  KEY `eb_work_channel_code_log_group_status_index` (`group_status`) USING BTREE,
  KEY `eb_work_channel_code_log_customer_status_index` (`customer_status`) USING BTREE,
  KEY `eb_work_channel_code_log_joined_at_index` (`joined_at`) USING BTREE,
  KEY `eb_work_channel_code_log_left_at_index` (`left_at`) USING BTREE,
  KEY `eb_work_channel_code_log_lost_at_index` (`lost_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企微渠道活码客户触达日志表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_label`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_label` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业微信id',
  `group_id` int NOT NULL DEFAULT '0' COMMENT '标签组id',
  `group_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标签组名称',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标签名称',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业微信标签';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_mass_messaging`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_mass_messaging` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '类型:1、群聊消息,0、群发消息,2、朋友圈消息;',
  `is_all` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否全部',
  `send_uid` json DEFAULT NULL COMMENT '发送用户ID',
  `send_group` json DEFAULT NULL COMMENT '发送群聊ID',
  `send_customer` json DEFAULT NULL COMMENT '发送客户ID',
  `search` json DEFAULT NULL COMMENT '搜索条件',
  `is_modify` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否允许修改',
  `temp_id` int unsigned NOT NULL DEFAULT '0' COMMENT '素材模板ID',
  `is_timed` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否定时发送',
  `send_time` timestamp NULL DEFAULT NULL COMMENT '发送时间',
  `be_sent` int unsigned NOT NULL DEFAULT '0' COMMENT '待发送',
  `is_send` int unsigned NOT NULL DEFAULT '0' COMMENT '已发送',
  `is_sent` int unsigned NOT NULL DEFAULT '0' COMMENT '已送达',
  `not_sent` int unsigned NOT NULL DEFAULT '0' COMMENT '未发送',
  `sent_uid` json DEFAULT NULL COMMENT '已发送员工ID',
  `not_sent_uid` json DEFAULT NULL COMMENT '未发送员工ID',
  `msg_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '群发消息ID',
  `fail_list` json DEFAULT NULL COMMENT '无效或无法发送的external_userid或chatid列表',
  `status` int unsigned NOT NULL DEFAULT '1' COMMENT '状态:0、关闭,1、开启,2、执行中,3、完成;',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_mass_messaging_uid_index` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='企微群发消息表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_mass_messaging_result`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_mass_messaging_result` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `task_id` int unsigned NOT NULL DEFAULT '0' COMMENT '群发任务ID',
  `msgid` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT '群发消息ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `chat_id` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '客户群ID',
  `external_userid` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '客户ID',
  `userid` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '员工ID',
  `is_comment` int unsigned NOT NULL DEFAULT '0' COMMENT '是否评论',
  `is_like` int unsigned NOT NULL DEFAULT '0' COMMENT '是否点赞',
  `status` int unsigned NOT NULL DEFAULT '1' COMMENT '发送状态：0-未发送 1-已发送 2-因客户不是好友导致发送失败 3-因客户已经收到其他群发消息导致发送失败',
  `send_time` timestamp NULL DEFAULT NULL COMMENT '发送时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_mass_messaging_result_uid_index` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='企微消息群发结果表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_mass_messaging_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_mass_messaging_task` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `mass_id` int unsigned NOT NULL DEFAULT '0' COMMENT '群发任务ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `userid` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '员工ID',
  `msgid` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '群发消息id',
  `moment_id` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '朋友圈id',
  `jobid` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '朋友圈任务id',
  `status` int unsigned NOT NULL DEFAULT '0' COMMENT '发送状态：0-未发送 2-已发送',
  `sum_count` int unsigned NOT NULL DEFAULT '0' COMMENT '发送人数',
  `not_send_count` int unsigned NOT NULL DEFAULT '0' COMMENT '未发送人数',
  `success_count` int unsigned NOT NULL DEFAULT '0' COMMENT '成功人数',
  `fail_count` int unsigned NOT NULL DEFAULT '0' COMMENT '失败人数',
  `types` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '类型:1、群聊消息,0、群发消息,2、朋友圈消息;',
  `fail_list` json DEFAULT NULL COMMENT '无效或无法发送的external_userid或chatid列表',
  `send_time` timestamp NULL DEFAULT NULL COMMENT '发送时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_mass_messaging_task_uid_index` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='企微群发成员发送任务表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_mass_messaging_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_mass_messaging_temp` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `group_id` int unsigned NOT NULL DEFAULT '0' COMMENT '分组ID',
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '内容',
  `types` int NOT NULL DEFAULT '0',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_mass_messaging_temp_uid_index` (`uid`) USING BTREE,
  KEY `eb_work_mass_messaging_temp_group_id_index` (`group_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='企微群发素材';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_mass_messaging_temp_attach`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_mass_messaging_temp_attach` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `temp_id` int unsigned NOT NULL DEFAULT '0' COMMENT '素材ID',
  `types` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '内容类型',
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `info` varchar(512) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '摘要',
  `link` varchar(512) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '链接',
  `app_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '小程序AppID',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_mass_messaging_temp_attach_uid_index` (`uid`) USING BTREE,
  KEY `eb_work_mass_messaging_temp_attach_temp_id_index` (`temp_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='素材附件表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_mass_messaging_temp_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_mass_messaging_temp_group` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父级分组ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '分组名称',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_mass_messaging_temp_group_pid_index` (`pid`) USING BTREE,
  KEY `eb_work_mass_messaging_temp_group_uid_index` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='企微群发素材分组';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '业务员用户ID',
  `real_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '原始名称',
  `file_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '附件名称',
  `file_url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '附件地址',
  `file_size` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '附件大小',
  `file_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '附件类型',
  `file_ext` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '扩展名',
  `file_md5` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `up_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '上传方式：1、本地；2、七牛云；3、OSS；4、COS。',
  `link_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联数据ID',
  `link_type` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '关联数据类型',
  `media_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT '临时素材ID',
  `attach_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT '临时附件ID',
  `attach_fail` timestamp NULL DEFAULT NULL COMMENT '临时附件过期时间',
  `media_type` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '素材类型: image、voice、video、file',
  `fail_time` timestamp NULL DEFAULT NULL COMMENT '临时素材失效时间',
  `media_msg` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `attach_msg` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `job_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_media_uid_index` (`uid`) USING BTREE,
  KEY `eb_work_media_link_id_index` (`link_id`) USING BTREE,
  KEY `eb_work_media_link_type_index` (`link_type`) USING BTREE,
  KEY `eb_work_media_media_id_index` (`media_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='临时素材表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_member` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业微信id',
  `userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '成员UserID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '成员名称',
  `position` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '职务信息',
  `mobile` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号码',
  `gender` tinyint NOT NULL DEFAULT '0' COMMENT '性别。0表示未定义，1表示男性，2表示女性',
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `biz_mail` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '企业邮箱',
  `direct_leader` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '直属上级UserID',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像url',
  `thumb_avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像缩略图url',
  `telephone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '座机',
  `alias` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '别名',
  `enable` tinyint NOT NULL DEFAULT '0' COMMENT '启用/禁用成员。1表示启用成员，0表示禁用成员',
  `is_leader` tinyint NOT NULL DEFAULT '0' COMMENT '是否是领导',
  `hide_mobile` tinyint NOT NULL DEFAULT '0' COMMENT '是否隐藏手机号',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '地址',
  `open_userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '全局唯一',
  `main_department` int unsigned NOT NULL DEFAULT '0' COMMENT '主部门',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '激活状态: 1=已激活，2=已禁用，4=未激活，5=退出企业',
  `qr_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '员工个人二维码',
  `external_position` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '对外职务',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业微信成员';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_member_other`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_member_other` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL DEFAULT '0' COMMENT '企业成员id',
  `extattr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '扩展属性',
  `external_profile` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '成员对外属性',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业微信成员其他信息';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_member_relation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_member_relation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL COMMENT '员工ID',
  `department` int NOT NULL COMMENT '所属部门',
  `srot` int NOT NULL COMMENT '排序',
  `is_leader_in_dept` tinyint NOT NULL COMMENT '是否为部门负责人',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企业成员关联表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_message_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_message_seq` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '企业ID',
  `seq` int NOT NULL COMMENT '序列号',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='企微消息序列号';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_reply_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_reply_temp` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `group_id` int unsigned NOT NULL DEFAULT '0' COMMENT '分租ID',
  `types` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '内容类型',
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `info` varchar(512) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '摘要',
  `link` varchar(512) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '链接',
  `app_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '小程序AppID',
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '文本内容',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_personal` tinyint NOT NULL DEFAULT '0' COMMENT '是否个人库: 0=公共, 1=个人',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_reply_temp_uid_index` (`uid`) USING BTREE,
  KEY `eb_work_reply_temp_group_id_index` (`group_id`) USING BTREE,
  KEY `is_personal` (`is_personal`),
  KEY `idx_uid_is_personal` (`uid`,`is_personal`),
  KEY `idx_sort` (`is_personal`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='快捷回复表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_work_reply_temp_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eb_work_reply_temp_group` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父级分租ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户ID',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '分租名称',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eb_work_reply_temp_group_pid_index` (`pid`) USING BTREE,
  KEY `eb_work_reply_temp_group_uid_index` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='快捷回复分组表';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- =============================================
-- 默认 MCP 服务（含 v2.6 审批内置）
-- =============================================
INSERT INTO `eb_chat_mcp_services` (`name`, `info`, `config_json`, `status`, `is_default`, `sort`, `created_at`, `updated_at`) VALUES
('客户MCP服务', '客户、线索、商机、订单、合同、发票、客户账目、联系人、跟进记录', '{"transport":"sse","module":"customer","headers":[],"timeout":30}', 1, 1, 0, NOW(), NOW()),
('考勤MCP服务', '考勤打卡、排班、申请和统计', '{"transport":"sse","module":"attendance","headers":[],"timeout":30}', 1, 1, 1, NOW(), NOW()),
('绩效MCP服务', '绩效列表、详情、统计和趋势', '{"transport":"sse","module":"assess","headers":[],"timeout":30}', 1, 1, 2, NOW(), NOW()),
('汇报MCP服务', '工作汇报列表、详情和统计', '{"transport":"sse","module":"report","headers":[],"timeout":30}', 1, 1, 3, NOW(), NOW()),
('日程MCP服务', '日程列表和详情', '{"transport":"sse","module":"schedule","headers":[],"timeout":30}', 1, 1, 4, NOW(), NOW()),
('审批MCP服务', '列出可申请审批类型、假期类型、获取审批表单、计算流程、发起审批、查看我发起的/待我审批', '{"transport":"sse","module":"approve","headers":[],"timeout":30}', 1, 1, 5, NOW(), NOW());

-- =============================================
-- v2.5 仓储库存表结构
-- =============================================
DROP TABLE IF EXISTS `eb_warehouse`;
CREATE TABLE `eb_warehouse` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '仓库名称',
  `code` varchar(64) NOT NULL DEFAULT '' COMMENT '仓库编码',
  `contact` varchar(64) NOT NULL DEFAULT '' COMMENT '联系人',
  `phone` varchar(32) NOT NULL DEFAULT '' COMMENT '联系电话',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '仓库地址',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：0停用 1启用',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` text COMMENT '备注',
  `creator` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_code_unique` (`code`),
  KEY `warehouse_status_index` (`status`),
  KEY `warehouse_creator_index` (`creator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='仓库表';

DROP TABLE IF EXISTS `eb_warehouse_manager`;
CREATE TABLE `eb_warehouse_manager` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '库管名称',
  `manager_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '库管类型：0普通 1超级管理员',
  `can_view_other` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否可查看其他仓库库存',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：0停用 1启用',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` text COMMENT '备注',
  `creator` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_manager_status_index` (`status`),
  KEY `warehouse_manager_creator_index` (`creator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='库管角色表';

DROP TABLE IF EXISTS `eb_warehouse_manager_user`;
CREATE TABLE `eb_warehouse_manager_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `manager_id` bigint unsigned NOT NULL COMMENT '库管ID',
  `user_id` int unsigned NOT NULL COMMENT '人员ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_warehouse_manager_user` (`manager_id`,`user_id`),
  KEY `warehouse_manager_user_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='库管人员关联表';

DROP TABLE IF EXISTS `eb_warehouse_manager_warehouse`;
CREATE TABLE `eb_warehouse_manager_warehouse` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `manager_id` bigint unsigned NOT NULL COMMENT '库管ID',
  `warehouse_id` bigint unsigned NOT NULL COMMENT '仓库ID',
  `can_stock` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '查看库存权限',
  `can_stock_setting` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '库存设置权限',
  `can_inbound` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '入库权限',
  `can_outbound` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '出库权限',
  `can_check` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '盘点权限',
  `can_transfer` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '调拨权限',
  `can_convert` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '正次转换权限',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_warehouse_manager_warehouse` (`manager_id`,`warehouse_id`),
  KEY `warehouse_manager_warehouse_warehouse_id_index` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='库管仓库关联表';

DROP TABLE IF EXISTS `eb_warehouse_sku_stock`;
CREATE TABLE `eb_warehouse_sku_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `warehouse_id` bigint unsigned NOT NULL COMMENT '仓库ID',
  `product_id` bigint unsigned NOT NULL COMMENT '产品ID',
  `sku_id` bigint unsigned NOT NULL COMMENT 'SKU ID',
  `good_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '良品库存',
  `defect_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '残次品库存',
  `occupy_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '占用库存',
  `transit_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '在途库存',
  `low_limit` int unsigned DEFAULT NULL COMMENT '库存下限',
  `high_limit` int unsigned DEFAULT NULL COMMENT '库存上限',
  `stock_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '库存状态',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_warehouse_sku_stock` (`warehouse_id`,`sku_id`),
  KEY `warehouse_sku_stock_product_id_index` (`product_id`),
  KEY `warehouse_sku_stock_sku_id_index` (`sku_id`),
  KEY `warehouse_sku_stock_stock_status_index` (`stock_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SKU仓库库存表';

DROP TABLE IF EXISTS `eb_warehouse_stock_flow`;
CREATE TABLE `eb_warehouse_stock_flow` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `warehouse_id` bigint unsigned NOT NULL COMMENT '仓库ID',
  `target_warehouse_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '目标仓库ID',
  `product_id` bigint unsigned NOT NULL COMMENT '产品ID',
  `sku_id` bigint unsigned NOT NULL COMMENT 'SKU ID',
  `flow_type` tinyint unsigned NOT NULL COMMENT '变动类型：1入库、2出库、3占用、4释放占用、5在途、6释放在途、7盘盈、8盘亏、9调拨出库、10调拨入库、11正残转换',
  `stock_quality` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '库存类型：1正品、2次品、3占用、4在途',
  `source_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '来源类型：0手工、1入库、2出库、3盘点、4调拨、5正残转换、6采购订单、7退货订单、8订单、9合同、10销售订单',
  `source_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '来源ID',
  `source_no` varchar(64) NOT NULL DEFAULT '' COMMENT '来源单号',
  `change_num` int NOT NULL DEFAULT '0' COMMENT '变动数量',
  `before_num` int NOT NULL DEFAULT '0' COMMENT '变动前数量',
  `after_num` int NOT NULL DEFAULT '0' COMMENT '变动后数量',
  `operator` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `remark` text COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_stock_flow_warehouse_id_index` (`warehouse_id`),
  KEY `warehouse_stock_flow_source_type_index` (`source_type`),
  KEY `warehouse_stock_flow_source_id_index` (`source_id`),
  KEY `warehouse_stock_flow_source_no_index` (`source_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='库存流水表';

DROP TABLE IF EXISTS `eb_warehouse_inbound`;
CREATE TABLE `eb_warehouse_inbound` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `bill_no` varchar(64) NOT NULL COMMENT '单据编号',
  `warehouse_id` bigint unsigned NOT NULL COMMENT '仓库ID',
  `inbound_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '入库类型：1采购入库、2退货入库、3其他入库',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `source_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '来源类型：0手工、1入库、2出库、3盘点、4调拨、5正残转换、6采购订单、7退货订单、8订单、9合同、10销售订单',
  `source_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '来源ID',
  `source_no` varchar(64) NOT NULL DEFAULT '' COMMENT '来源订单号',
  `total_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '入库总金额',
  `images` json DEFAULT NULL COMMENT '凭证附件ID数组',
  `remark` text COMMENT '备注',
  `creator` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `operator` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `operated_at` timestamp NULL DEFAULT NULL COMMENT '操作时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_inbound_bill_no_unique` (`bill_no`),
  KEY `warehouse_inbound_warehouse_id_index` (`warehouse_id`),
  KEY `warehouse_inbound_inbound_type_index` (`inbound_type`),
  KEY `warehouse_inbound_source_no_index` (`source_no`),
  KEY `idx_warehouse_inbound_source` (`source_type`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='入库单主表';

DROP TABLE IF EXISTS `eb_warehouse_inbound_detail`;
CREATE TABLE `eb_warehouse_inbound_detail` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `inbound_id` bigint unsigned NOT NULL COMMENT '主单ID',
  `product_id` bigint unsigned NOT NULL COMMENT '产品ID',
  `sku_id` bigint unsigned NOT NULL COMMENT 'SKU ID',
  `stock_quality` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '库存属性：1正品、2次品',
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '入库单价',
  `quantity` int unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `gift_quantity` int unsigned NOT NULL DEFAULT '0' COMMENT '赠送数量',
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '金额小计',
  `remark` text COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_inbound_detail_inbound_id_index` (`inbound_id`),
  KEY `warehouse_inbound_detail_product_id_index` (`product_id`),
  KEY `warehouse_inbound_detail_sku_id_index` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='入库单明细表';

DROP TABLE IF EXISTS `eb_warehouse_outbound`;
CREATE TABLE `eb_warehouse_outbound` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `bill_no` varchar(64) NOT NULL COMMENT '单据编号',
  `warehouse_id` bigint unsigned NOT NULL COMMENT '仓库ID',
  `outbound_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '出库类型：1销售出库、2过期退货、3试用出库、4其他出库',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `source_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '来源类型：0手工、1入库、2出库、3盘点、4调拨、5正残转换、6采购订单、7退货订单、8订单、9合同、10销售订单',
  `source_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '来源ID',
  `source_no` varchar(64) NOT NULL DEFAULT '' COMMENT '来源订单号',
  `images` json DEFAULT NULL COMMENT '凭证附件ID数组',
  `remark` text COMMENT '备注',
  `creator` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `operator` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `operated_at` timestamp NULL DEFAULT NULL COMMENT '操作时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_outbound_bill_no_unique` (`bill_no`),
  KEY `warehouse_outbound_warehouse_id_index` (`warehouse_id`),
  KEY `warehouse_outbound_outbound_type_index` (`outbound_type`),
  KEY `warehouse_outbound_source_no_index` (`source_no`),
  KEY `idx_warehouse_outbound_source` (`source_type`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='出库单主表';

DROP TABLE IF EXISTS `eb_warehouse_outbound_detail`;
CREATE TABLE `eb_warehouse_outbound_detail` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `outbound_id` bigint unsigned NOT NULL COMMENT '主单ID',
  `product_id` bigint unsigned NOT NULL COMMENT '产品ID',
  `sku_id` bigint unsigned NOT NULL COMMENT 'SKU ID',
  `stock_quality` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '库存属性：1正品、2次品',
  `quantity` int unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `remark` text COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_outbound_detail_outbound_id_index` (`outbound_id`),
  KEY `warehouse_outbound_detail_product_id_index` (`product_id`),
  KEY `warehouse_outbound_detail_sku_id_index` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='出库单明细表';

DROP TABLE IF EXISTS `eb_warehouse_check`;
CREATE TABLE `eb_warehouse_check` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `bill_no` varchar(64) NOT NULL COMMENT '单据编号',
  `warehouse_id` bigint unsigned NOT NULL COMMENT '仓库ID',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `source_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '来源类型：0手工、1入库、2出库、3盘点、4调拨、5正残转换、6采购订单、7退货订单、8订单、9合同、10销售订单',
  `source_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '来源ID',
  `images` json DEFAULT NULL COMMENT '凭证附件ID数组',
  `remark` text COMMENT '备注',
  `creator` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `operator` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `operated_at` timestamp NULL DEFAULT NULL COMMENT '操作时间',
  `finished_at` timestamp NULL DEFAULT NULL COMMENT '完成时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_check_bill_no_unique` (`bill_no`),
  KEY `warehouse_check_warehouse_id_index` (`warehouse_id`),
  KEY `idx_warehouse_check_source` (`source_type`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='盘点单主表';

DROP TABLE IF EXISTS `eb_warehouse_check_detail`;
CREATE TABLE `eb_warehouse_check_detail` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `check_id` bigint unsigned NOT NULL COMMENT '主单ID',
  `product_id` bigint unsigned NOT NULL COMMENT '产品ID',
  `sku_id` bigint unsigned NOT NULL COMMENT 'SKU ID',
  `quantity` int unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `book_good_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '账面良品库存',
  `book_defect_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '账面残次品库存',
  `real_good_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '实盘良品库存',
  `real_defect_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '实盘残次品库存',
  `remark` text COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_check_detail_check_id_index` (`check_id`),
  KEY `warehouse_check_detail_product_id_index` (`product_id`),
  KEY `warehouse_check_detail_sku_id_index` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='盘点单明细表';

DROP TABLE IF EXISTS `eb_warehouse_transfer`;
CREATE TABLE `eb_warehouse_transfer` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `bill_no` varchar(64) NOT NULL COMMENT '单据编号',
  `out_warehouse_id` bigint unsigned NOT NULL COMMENT '调出仓库ID',
  `in_warehouse_id` bigint unsigned NOT NULL COMMENT '调入仓库ID',
  `transfer_type` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '调拨类型：1直接 2物流',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `images` json DEFAULT NULL COMMENT '凭证附件ID数组',
  `remark` text COMMENT '备注',
  `creator` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `operator` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `out_at` timestamp NULL DEFAULT NULL COMMENT '出库时间',
  `in_at` timestamp NULL DEFAULT NULL COMMENT '入库时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_transfer_bill_no_unique` (`bill_no`),
  KEY `warehouse_transfer_out_warehouse_id_index` (`out_warehouse_id`),
  KEY `warehouse_transfer_in_warehouse_id_index` (`in_warehouse_id`),
  KEY `warehouse_transfer_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='调拨单主表';

DROP TABLE IF EXISTS `eb_warehouse_transfer_detail`;
CREATE TABLE `eb_warehouse_transfer_detail` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `transfer_id` bigint unsigned NOT NULL COMMENT '主单ID',
  `product_id` bigint unsigned NOT NULL COMMENT '产品ID',
  `sku_id` bigint unsigned NOT NULL COMMENT 'SKU ID',
  `stock_quality` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '库存属性：1正品、2次品',
  `quantity` int unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `remark` text COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_transfer_detail_transfer_id_index` (`transfer_id`),
  KEY `warehouse_transfer_detail_product_id_index` (`product_id`),
  KEY `warehouse_transfer_detail_sku_id_index` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='调拨单明细表';

DROP TABLE IF EXISTS `eb_warehouse_convert`;
CREATE TABLE `eb_warehouse_convert` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `bill_no` varchar(64) NOT NULL COMMENT '单据编号',
  `warehouse_id` bigint unsigned NOT NULL COMMENT '仓库ID',
  `convert_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '转换类型：1良转残 2残转良',
  `status` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '状态',
  `images` json DEFAULT NULL COMMENT '凭证附件ID数组',
  `remark` text COMMENT '备注',
  `creator` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `operator` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `operated_at` timestamp NULL DEFAULT NULL COMMENT '操作时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_convert_bill_no_unique` (`bill_no`),
  KEY `warehouse_convert_warehouse_id_index` (`warehouse_id`),
  KEY `warehouse_convert_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正次转换单主表';

DROP TABLE IF EXISTS `eb_warehouse_convert_detail`;
CREATE TABLE `eb_warehouse_convert_detail` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `convert_id` bigint unsigned NOT NULL COMMENT '主单ID',
  `product_id` bigint unsigned NOT NULL COMMENT '产品ID',
  `sku_id` bigint unsigned NOT NULL COMMENT 'SKU ID',
  `quantity` int unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `remark` text COMMENT '备注',
  `images` json DEFAULT NULL COMMENT '商品凭证附件ID数组',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_convert_detail_convert_id_index` (`convert_id`),
  KEY `warehouse_convert_detail_product_id_index` (`product_id`),
  KEY `warehouse_convert_detail_sku_id_index` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='正次转换单明细表';

DROP TABLE IF EXISTS `eb_warehouse_stock_limit`;
CREATE TABLE `eb_warehouse_stock_limit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `warehouse_id` bigint unsigned NOT NULL COMMENT '仓库ID',
  `product_id` bigint unsigned NOT NULL COMMENT '产品ID',
  `sku_id` bigint unsigned NOT NULL COMMENT 'SKU ID',
  `low_limit` int unsigned DEFAULT NULL COMMENT '库存下限',
  `high_limit` int unsigned DEFAULT NULL COMMENT '库存上限',
  `operator` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_warehouse_stock_limit` (`warehouse_id`,`sku_id`),
  KEY `warehouse_stock_limit_product_id_index` (`product_id`),
  KEY `warehouse_stock_limit_sku_id_index` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='库存上下限设置表';

DROP TABLE IF EXISTS `eb_warehouse_order_stock_config`;
CREATE TABLE `eb_warehouse_order_stock_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_source` varchar(40) NOT NULL DEFAULT 'contract' COMMENT '订单来源',
  `auto_create_outbound` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否自动生成出库单',
  `deduct_after_paid` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否付款后出库',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `remark` text COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_order_stock_config_order_source_unique` (`order_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单库存配置表';
