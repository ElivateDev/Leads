CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "clients"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "phone" varchar,
  "company" varchar,
  "email_notifications" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "lead_dispositions" text,
  "notification_emails" text
);
CREATE UNIQUE INDEX "clients_email_unique" on "clients"("email");
CREATE TABLE IF NOT EXISTS "client_emails"(
  "id" integer primary key autoincrement not null,
  "client_id" integer not null,
  "email" varchar,
  "description" varchar,
  "is_active" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "custom_conditions" text,
  "rule_type" varchar check("rule_type" in('email_match', 'custom_rule', 'combined_rule')) not null default 'email_match',
  foreign key("client_id") references clients("id") on delete cascade on update no action
);
CREATE INDEX "client_emails_email_index" on "client_emails"("email");
CREATE INDEX "client_emails_is_active_rule_type_index" on "client_emails"(
  "is_active",
  "rule_type"
);
CREATE INDEX "client_emails_email_is_active_index" on "client_emails"(
  "email",
  "is_active"
);
CREATE INDEX "client_emails_rule_type_is_active_index" on "client_emails"(
  "rule_type",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "email_processing_logs"(
  "id" integer primary key autoincrement not null,
  "email_id" varchar,
  "from_address" varchar not null,
  "subject" varchar,
  "type" varchar check("type" in('email_received', 'rule_matched', 'rule_failed', 'lead_created', 'lead_duplicate', 'notification_sent', 'error')) not null,
  "status" varchar check("status" in('success', 'failed', 'skipped')) not null,
  "client_id" integer,
  "lead_id" integer,
  "rule_id" integer,
  "rule_type" varchar,
  "message" text not null,
  "details" text,
  "processed_at" datetime not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("client_id") references "clients"("id") on delete cascade,
  foreign key("lead_id") references "leads"("id") on delete cascade,
  foreign key("rule_id") references "client_emails"("id") on delete cascade
);
CREATE INDEX "email_processing_logs_type_status_index" on "email_processing_logs"(
  "type",
  "status"
);
CREATE INDEX "email_processing_logs_from_address_processed_at_index" on "email_processing_logs"(
  "from_address",
  "processed_at"
);
CREATE INDEX "email_processing_logs_client_id_processed_at_index" on "email_processing_logs"(
  "client_id",
  "processed_at"
);
CREATE INDEX "email_processing_logs_processed_at_index" on "email_processing_logs"(
  "processed_at"
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "client_id" integer,
  "role" varchar check("role" in('admin', 'client')) not null default 'admin',
  foreign key("client_id") references "clients"("id") on delete cascade
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "leads"(
  "id" integer primary key autoincrement not null,
  "client_id" integer not null,
  "name" varchar not null,
  "email" varchar,
  "phone" varchar,
  "message" text,
  "from_email" varchar,
  "status" varchar not null default 'new',
  "source" varchar not null default('website'),
  "created_at" datetime,
  "updated_at" datetime,
  "email_subject" varchar,
  "email_received_at" datetime,
  "notes" text,
  "campaign" varchar,
  foreign key("client_id") references clients("id") on delete cascade on update no action
);
CREATE TABLE IF NOT EXISTS "user_preferences"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "key" varchar not null,
  "value" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "user_preferences_user_id_key_unique" on "user_preferences"(
  "user_id",
  "key"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(6,'2025_06_20_180829_create_clients_table',2);
INSERT INTO migrations VALUES(7,'2025_06_20_180955_create_leads_table',2);
INSERT INTO migrations VALUES(8,'2025_06_21_202344_create_client_emails_table',3);
INSERT INTO migrations VALUES(9,'2025_06_28_224115_add_distribution_rule_fields_to_client_emails_table',4);
INSERT INTO migrations VALUES(10,'2025_06_28_225628_add_combined_rule_type_to_client_emails_table',5);
INSERT INTO migrations VALUES(11,'2025_06_28_233127_add_indexes_to_client_emails_table',6);
INSERT INTO migrations VALUES(12,'2025_06_29_010019_create_email_processing_logs_table',7);
INSERT INTO migrations VALUES(13,'2025_07_18_160356_add_client_and_role_to_users_table',8);
INSERT INTO migrations VALUES(14,'2025_07_18_162436_add_email_subject_to_leads_table',9);
INSERT INTO migrations VALUES(15,'2025_07_24_145226_add_lead_dispositions_to_clients_table',10);
INSERT INTO migrations VALUES(16,'2025_07_24_145414_change_status_column_in_leads_table',10);
INSERT INTO migrations VALUES(17,'2025_07_24_150437_add_notes_to_leads_table',11);
INSERT INTO migrations VALUES(18,'2025_07_24_154720_add_notification_emails_to_clients_table',12);
INSERT INTO migrations VALUES(19,'2025_07_24_181051_create_user_preferences_table',13);
INSERT INTO migrations VALUES(20,'2025_07_25_100700_add_campaign_to_leads_table',14);
