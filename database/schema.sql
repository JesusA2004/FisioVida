SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- =========================
-- PERSONAS (paciente / staff / ambos)
-- =========================
CREATE TABLE personas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('paciente','staff','ambos') NOT NULL DEFAULT 'paciente',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',

  nombres VARCHAR(120) NOT NULL,
  apellido_paterno VARCHAR(120) NULL,
  apellido_materno VARCHAR(120) NULL,
  fecha_nacimiento DATE NULL,
  sexo ENUM('M','F','X') NULL,

  telefono VARCHAR(30) NULL,
  email VARCHAR(190) NULL,
  direccion VARCHAR(255) NULL,

  -- datos opcionales (lo que antes iba en patients)
  contacto_emergencia_nombre VARCHAR(190) NULL,
  contacto_emergencia_telefono VARCHAR(30) NULL,
  notas TEXT NULL,

  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,

  INDEX idx_personas_nombre (apellido_paterno, apellido_materno, nombres),
  INDEX idx_personas_tipo (tipo, status),
  INDEX idx_personas_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- USERS (login + permisos por módulo por usuario)
-- =========================
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  persona_id BIGINT UNSIGNED NULL,

  name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password VARCHAR(255) NOT NULL,
  status ENUM('active','blocked') NOT NULL DEFAULT 'active',

  is_super_admin TINYINT(1) NOT NULL DEFAULT 0,

  -- Permisos por módulo (banderas). El Super Admin los ajusta por usuario.
  mod_agenda     TINYINT(1) NOT NULL DEFAULT 1,
  mod_pacientes  TINYINT(1) NOT NULL DEFAULT 1,
  mod_sesiones   TINYINT(1) NOT NULL DEFAULT 1,
  mod_ejercicios TINYINT(1) NOT NULL DEFAULT 1,
  mod_archivos   TINYINT(1) NOT NULL DEFAULT 1,
  mod_reportes   TINYINT(1) NOT NULL DEFAULT 0,
  mod_cobranza   TINYINT(1) NOT NULL DEFAULT 0,
  mod_config     TINYINT(1) NOT NULL DEFAULT 0,

  last_login_at TIMESTAMP NULL,
  last_login_ip VARCHAR(45) NULL,
  remember_token VARCHAR(100) NULL,

  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,

  UNIQUE KEY uq_users_email (email),
  INDEX idx_users_status (status),
  CONSTRAINT fk_users_persona FOREIGN KEY (persona_id) REFERENCES personas(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- AGENDA / CITAS
-- =========================
CREATE TABLE appointments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  patient_persona_id BIGINT UNSIGNED NOT NULL,  -- paciente = persona
  therapist_user_id BIGINT UNSIGNED NOT NULL,

  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,

  status ENUM('scheduled','confirmed','arrived','no_show','cancelled','done')
    NOT NULL DEFAULT 'scheduled',

  notes VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,

  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  INDEX idx_appt_therapist_time (therapist_user_id, start_at),
  INDEX idx_appt_patient_time (patient_persona_id, start_at),

  CONSTRAINT fk_appt_patient_persona FOREIGN KEY (patient_persona_id) REFERENCES personas(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_appt_therapist FOREIGN KEY (therapist_user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_appt_creator FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- SESIONES (registro clínico rápido tipo SOAP)
-- =========================
CREATE TABLE therapy_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  appointment_id BIGINT UNSIGNED NULL,
  patient_persona_id BIGINT UNSIGNED NOT NULL,
  therapist_user_id BIGINT UNSIGNED NOT NULL,

  session_date DATE NOT NULL,

  subjective TEXT NULL,
  objective TEXT NULL,
  assessment TEXT NULL,
  plan TEXT NULL,

  pain_scale TINYINT UNSIGNED NULL, -- 0-10 (valídalo en app)
  notes TEXT NULL,

  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  INDEX idx_sessions_patient_date (patient_persona_id, session_date),
  INDEX idx_sessions_therapist_date (therapist_user_id, session_date),

  CONSTRAINT fk_session_appt FOREIGN KEY (appointment_id) REFERENCES appointments(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_session_patient_persona FOREIGN KEY (patient_persona_id) REFERENCES personas(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_session_therapist FOREIGN KEY (therapist_user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- EJERCICIOS (catálogo simple)
-- =========================
CREATE TABLE exercises (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  description TEXT NULL,
  video_url VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_exercises_active (is_active, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE session_exercises (
  session_id BIGINT UNSIGNED NOT NULL,
  exercise_id BIGINT UNSIGNED NOT NULL,
  sets INT UNSIGNED NULL,
  reps INT UNSIGNED NULL,
  seconds INT UNSIGNED NULL,
  notes VARCHAR(255) NULL,
  PRIMARY KEY (session_id, exercise_id),
  CONSTRAINT fk_sx_session FOREIGN KEY (session_id) REFERENCES therapy_sessions(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_sx_exercise FOREIGN KEY (exercise_id) REFERENCES exercises(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- ARCHIVOS (estudios, consentimientos, etc.)
-- =========================
CREATE TABLE files (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  patient_persona_id BIGINT UNSIGNED NULL,
  session_id BIGINT UNSIGNED NULL,
  uploaded_by BIGINT UNSIGNED NULL,

  disk VARCHAR(30) NOT NULL DEFAULT 'public',
  path VARCHAR(255) NOT NULL,
  original_name VARCHAR(190) NOT NULL,
  mime VARCHAR(80) NULL,
  size_bytes BIGINT UNSIGNED NULL,

  created_at TIMESTAMP NULL,

  INDEX idx_files_patient (patient_persona_id, created_at),

  CONSTRAINT fk_files_patient_persona FOREIGN KEY (patient_persona_id) REFERENCES personas(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_files_session FOREIGN KEY (session_id) REFERENCES therapy_sessions(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_files_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- PAGOS (mínimo para pagos en línea; sin suscripciones)
-- =========================
CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  provider VARCHAR(50) NOT NULL,                 -- stripe/mercadopago/etc
  provider_payment_id VARCHAR(120) NULL,
  amount DECIMAL(12,2) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'MXN',
  status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  paid_at TIMESTAMP NULL,
  reference VARCHAR(120) NULL,
  notes VARCHAR(255) NULL,

  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  INDEX idx_pay_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- LOGS (uno solo, sin JSON)
-- =========================
CREATE TABLE logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  level ENUM('info','warning','error','audit') NOT NULL DEFAULT 'info',
  actor_user_id BIGINT UNSIGNED NULL,

  action VARCHAR(120) NOT NULL,      -- ej: personas.update, appointments.cancel
  entity_type VARCHAR(80) NULL,      -- ej: persona, appointment, session
  entity_id BIGINT UNSIGNED NULL,

  message VARCHAR(255) NOT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,

  created_at TIMESTAMP NULL,

  INDEX idx_logs_level_time (level, created_at),
  INDEX idx_logs_actor_time (actor_user_id, created_at),

  CONSTRAINT fk_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
