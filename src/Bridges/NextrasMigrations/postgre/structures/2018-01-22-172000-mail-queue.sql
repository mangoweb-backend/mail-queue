CREATE SEQUENCE "mangoweb_mail_queue_id_seq";

CREATE TABLE "mangoweb_mail_queue" (
	"id" int NOT NULL DEFAULT nextval('mangoweb_mail_queue_id_seq'),
	"created_at" timestamp without time zone NOT NULL,
	"sent_at" timestamp without time zone DEFAULT NULL,
	"last_failed_at" timestamp without time zone DEFAULT NULL,
	"failure_count" int DEFAULT '0',
	"failure_message" text DEFAULT NULL,
	"recipients" text NOT NULL,
	"subject" text NOT NULL,
	"body" text NOT NULL,
	"message" bytea NOT NULL,
	PRIMARY KEY ("id")
);

CREATE INDEX "mangoweb_mail_queue_sent_at_created_at" ON "mangoweb_mail_queue" ("sent_at", "created_at");
