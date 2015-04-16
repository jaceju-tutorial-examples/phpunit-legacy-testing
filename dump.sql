PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE "post" (
  "id" integer PRIMARY KEY,
  "nickname" varchar(20) NOT NULL,
  "message" text NOT NULL,
  "created_at" datetime NOT NULL
);
COMMIT;
