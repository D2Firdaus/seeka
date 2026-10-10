-- Lost & Found Kampus - skema MySQL/MariaDB (alternatif tanpa Laravel migration)
-- PK berupa kode string (USR001, ITM001, CLM001, ...)
-- Import via phpMyAdmin atau: mysql -u root -p < lost_and_found.sql

CREATE DATABASE IF NOT EXISTS lost_and_found
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lost_and_found;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS item_matches, item_embeddings, disputes, item_logs, handovers, claims, item_images, items, locations, categories, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  user_id           VARCHAR(10)  NOT NULL,              -- USR001
  name              VARCHAR(255) NOT NULL,
  email             VARCHAR(255) NOT NULL,
  email_verified_at TIMESTAMP NULL,
  password          VARCHAR(255) NOT NULL,
  nim_nip           VARCHAR(30) NULL,
  phone             VARCHAR(20) NULL,
  role              ENUM('admin','user') NOT NULL DEFAULT 'user',
  notify_email      TINYINT(1) NOT NULL DEFAULT 1,
  notify_whatsapp   TINYINT(1) NOT NULL DEFAULT 0,
  remember_token    VARCHAR(100) NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  PRIMARY KEY (user_id),
  UNIQUE KEY users_email_unique (email),
  UNIQUE KEY users_nim_nip_unique (nim_nip),
  KEY users_role_index (role)
) ENGINE=InnoDB;

CREATE TABLE categories (
  category_id VARCHAR(10)  NOT NULL,                    -- CTG001
  name        VARCHAR(100) NOT NULL,
  slug        VARCHAR(120) NOT NULL,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,
  PRIMARY KEY (category_id),
  UNIQUE KEY categories_slug_unique (slug)
) ENGINE=InnoDB;

CREATE TABLE locations (
  location_id VARCHAR(10)  NOT NULL,                    -- LOC001
  name        VARCHAR(150) NOT NULL,
  description TEXT NULL,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,
  PRIMARY KEY (location_id)
) ENGINE=InnoDB;

CREATE TABLE items (
  item_id          VARCHAR(10)  NOT NULL,               -- ITM001
  user_id          VARCHAR(10)  NOT NULL,
  category_id      VARCHAR(10)  NOT NULL,
  location_id      VARCHAR(10)  NOT NULL,
  type             ENUM('lost','found') NOT NULL,
  title            VARCHAR(255) NOT NULL,
  description      TEXT NOT NULL,
  hidden_detail    TEXT NULL,
  date_event       DATE NOT NULL,
  status           ENUM('open','claimed','returned','closed') NOT NULL DEFAULT 'open',
  storage_location VARCHAR(255) NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  deleted_at       TIMESTAMP NULL,
  PRIMARY KEY (item_id),
  KEY items_type_status_date_event_index (type, status, date_event),
  KEY items_date_event_index (date_event),
  KEY items_user_id_foreign (user_id),
  KEY items_category_id_foreign (category_id),
  KEY items_location_id_foreign (location_id),
  FULLTEXT KEY items_title_description_fulltext (title, description),
  CONSTRAINT items_user_id_foreign     FOREIGN KEY (user_id)     REFERENCES users (user_id)           ON DELETE RESTRICT,
  CONSTRAINT items_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories (category_id)  ON DELETE RESTRICT,
  CONSTRAINT items_location_id_foreign FOREIGN KEY (location_id) REFERENCES locations (location_id)   ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE item_images (
  image_id   VARCHAR(10)  NOT NULL,                     -- IMG001
  item_id    VARCHAR(10)  NOT NULL,
  path       VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (image_id),
  KEY item_images_item_id_foreign (item_id),
  CONSTRAINT item_images_item_id_foreign FOREIGN KEY (item_id) REFERENCES items (item_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE claims (
  claim_id          VARCHAR(10) NOT NULL,               -- CLM001
  item_id           VARCHAR(10) NOT NULL,
  claimant_id       VARCHAR(10) NOT NULL,
  lost_item_id      VARCHAR(10) NULL,                   -- laporan hilang milik pengaju (jika ada)
  proof_description TEXT NOT NULL,
  proof_image       VARCHAR(255) NULL,
  status            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  verified_by       VARCHAR(10) NULL,
  verified_at       TIMESTAMP NULL,
  admin_note        TEXT NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  PRIMARY KEY (claim_id),
  KEY claims_item_id_status_index (item_id, status),
  KEY claims_claimant_id_foreign (claimant_id),
  KEY claims_lost_item_id_foreign (lost_item_id),
  KEY claims_verified_by_foreign (verified_by),
  CONSTRAINT claims_item_id_foreign     FOREIGN KEY (item_id)     REFERENCES items (item_id)  ON DELETE CASCADE,
  CONSTRAINT claims_claimant_id_foreign FOREIGN KEY (claimant_id) REFERENCES users (user_id)  ON DELETE RESTRICT,
  CONSTRAINT claims_lost_item_id_foreign FOREIGN KEY (lost_item_id) REFERENCES items (item_id) ON DELETE SET NULL,
  CONSTRAINT claims_verified_by_foreign FOREIGN KEY (verified_by) REFERENCES users (user_id)  ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE handovers (
  handover_id   VARCHAR(10) NOT NULL,                   -- HND001
  claim_id      VARCHAR(10) NOT NULL,
  handed_by     VARCHAR(10) NOT NULL,
  received_by   VARCHAR(10) NOT NULL,
  handover_date DATETIME NOT NULL,
  photo         VARCHAR(255) NULL,
  note          TEXT NULL,
  created_at    TIMESTAMP NULL,
  updated_at    TIMESTAMP NULL,
  PRIMARY KEY (handover_id),
  UNIQUE KEY handovers_claim_id_unique (claim_id),
  KEY handovers_handed_by_foreign (handed_by),
  KEY handovers_received_by_foreign (received_by),
  CONSTRAINT handovers_claim_id_foreign    FOREIGN KEY (claim_id)    REFERENCES claims (claim_id) ON DELETE CASCADE,
  CONSTRAINT handovers_handed_by_foreign   FOREIGN KEY (handed_by)   REFERENCES users (user_id)   ON DELETE RESTRICT,
  CONSTRAINT handovers_received_by_foreign FOREIGN KEY (received_by) REFERENCES users (user_id)   ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE item_logs (
  log_id     VARCHAR(10) NOT NULL,                      -- LOG001
  item_id    VARCHAR(10) NOT NULL,
  user_id    VARCHAR(10) NOT NULL,
  old_status VARCHAR(30) NULL,
  new_status VARCHAR(30) NOT NULL,
  note       TEXT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (log_id),
  KEY item_logs_item_id_created_at_index (item_id, created_at),
  KEY item_logs_user_id_foreign (user_id),
  CONSTRAINT item_logs_item_id_foreign FOREIGN KEY (item_id) REFERENCES items (item_id) ON DELETE CASCADE,
  CONSTRAINT item_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE disputes (
  dispute_id  VARCHAR(10) NOT NULL,                     -- DSP001
  claim_id    VARCHAR(10) NOT NULL,
  reported_by VARCHAR(10) NOT NULL,
  reason      TEXT NOT NULL,
  status      ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  resolved_by VARCHAR(10) NULL,
  resolved_at TIMESTAMP NULL,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,
  PRIMARY KEY (dispute_id),
  KEY disputes_claim_id_status_index (claim_id, status),
  KEY disputes_reported_by_foreign (reported_by),
  KEY disputes_resolved_by_foreign (resolved_by),
  CONSTRAINT disputes_claim_id_foreign    FOREIGN KEY (claim_id)    REFERENCES claims (claim_id) ON DELETE CASCADE,
  CONSTRAINT disputes_reported_by_foreign FOREIGN KEY (reported_by) REFERENCES users (user_id)   ON DELETE RESTRICT,
  CONSTRAINT disputes_resolved_by_foreign FOREIGN KEY (resolved_by) REFERENCES users (user_id)   ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE item_embeddings (
  embedding_id VARCHAR(10)  NOT NULL,                   -- EMB001
  item_id      VARCHAR(10)  NOT NULL,
  model_name   VARCHAR(100) NOT NULL,
  text_hash    CHAR(64)     NOT NULL,
  embedding    BLOB         NOT NULL,                   -- vektor float32
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  PRIMARY KEY (embedding_id),
  UNIQUE KEY item_embeddings_item_id_model_name_unique (item_id, model_name),
  CONSTRAINT item_embeddings_item_id_foreign FOREIGN KEY (item_id) REFERENCES items (item_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Aturan yang dijaga aplikasi: lost_item_id harus items.type='lost', found_item_id harus items.type='found'
CREATE TABLE item_matches (
  match_id       VARCHAR(10)   NOT NULL,                -- MTC001
  lost_item_id   VARCHAR(10)   NOT NULL,
  found_item_id  VARCHAR(10)   NOT NULL,
  score          DECIMAL(5,4)  NOT NULL,
  sbert_cosine   DECIMAL(5,4)  NOT NULL,
  bm25_norm      DECIMAL(5,4)  NULL,
  metadata_score DECIMAL(5,4)  NOT NULL,
  status         ENUM('new','notified','viewed','dismissed') NOT NULL DEFAULT 'new',
  notified_at    TIMESTAMP NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  PRIMARY KEY (match_id),
  UNIQUE KEY item_matches_lost_item_id_found_item_id_unique (lost_item_id, found_item_id),
  KEY item_matches_status_index (status),
  KEY item_matches_found_item_id_foreign (found_item_id),
  CONSTRAINT item_matches_lost_item_id_foreign  FOREIGN KEY (lost_item_id)  REFERENCES items (item_id) ON DELETE CASCADE,
  CONSTRAINT item_matches_found_item_id_foreign FOREIGN KEY (found_item_id) REFERENCES items (item_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Data awal (opsional)
INSERT INTO categories (category_id, name, slug, created_at, updated_at) VALUES
  ('CTG001','Elektronik','elektronik',NOW(),NOW()),
  ('CTG002','Dokumen & Kartu','dokumen-kartu',NOW(),NOW()),
  ('CTG003','Tas & Dompet','tas-dompet',NOW(),NOW()),
  ('CTG004','Kunci','kunci',NOW(),NOW()),
  ('CTG005','Pakaian & Aksesori','pakaian-aksesori',NOW(),NOW()),
  ('CTG006','Alat Tulis & Buku','alat-tulis-buku',NOW(),NOW()),
  ('CTG007','Perlengkapan Olahraga','perlengkapan-olahraga',NOW(),NOW()),
  ('CTG008','Lainnya','lainnya',NOW(),NOW());

INSERT INTO locations (location_id, name, created_at, updated_at) VALUES
  ('LOC001','Gedung Rektorat',NOW(),NOW()),
  ('LOC002','Perpustakaan',NOW(),NOW()),
  ('LOC003','Kantin',NOW(),NOW()),
  ('LOC004','Masjid/Mushola',NOW(),NOW()),
  ('LOC005','Parkiran',NOW(),NOW()),
  ('LOC006','Lapangan',NOW(),NOW()),
  ('LOC007','Laboratorium',NOW(),NOW()),
  ('LOC008','Ruang Kelas',NOW(),NOW());
