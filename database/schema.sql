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
    direccion VARCHAR(255) NULL,
    contacto_emergencia_nombre VARCHAR(190) NULL,
    contacto_emergencia_telefono VARCHAR(30) NULL,
    notas TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    persona_id BIGINT UNSIGNED NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active','blocked') NOT NULL DEFAULT 'active',
    is_super_admin TINYINT(1) NOT NULL DEFAULT 0,
    mod_agenda TINYINT(1) NOT NULL DEFAULT 1,
    mod_pacientes TINYINT(1) NOT NULL DEFAULT 1,
    mod_sesiones TINYINT(1) NOT NULL DEFAULT 1,
    mod_ejercicios TINYINT(1) NOT NULL DEFAULT 1,
    mod_archivos TINYINT(1) NOT NULL DEFAULT 1,
    mod_reportes TINYINT(1) NOT NULL DEFAULT 0,
    mod_cobranza TINYINT(1) NOT NULL DEFAULT 0,
    mod_config TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_users_persona
        FOREIGN KEY (persona_id) REFERENCES personas(id)
        ON DELETE RESTRICT
);

CREATE TABLE password_reset_tokens (
    email VARCHAR(191) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);

CREATE TABLE sessions (
    id VARCHAR(191) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    CONSTRAINT fk_sessions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_persona_id BIGINT UNSIGNED NOT NULL,
    therapist_user_id BIGINT UNSIGNED NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    status ENUM('scheduled','confirmed','arrived','no_show','cancelled','done') NOT NULL DEFAULT 'scheduled',
    notes VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_appointments_patient
        FOREIGN KEY (patient_persona_id) REFERENCES personas(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_appointments_therapist
        FOREIGN KEY (therapist_user_id) REFERENCES users(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_appointments_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
);

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
    pain_scale TINYINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_therapy_sessions_appointment
        FOREIGN KEY (appointment_id) REFERENCES appointments(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_therapy_sessions_patient
        FOREIGN KEY (patient_persona_id) REFERENCES personas(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_therapy_sessions_therapist
        FOREIGN KEY (therapist_user_id) REFERENCES users(id)
        ON DELETE RESTRICT
);

CREATE TABLE exercises (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    video_url VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE session_exercises (
    session_id BIGINT UNSIGNED NOT NULL,
    exercise_id BIGINT UNSIGNED NOT NULL,
    sets INT UNSIGNED NULL,
    reps INT UNSIGNED NULL,
    seconds INT UNSIGNED NULL,
    notes VARCHAR(255) NULL,
    PRIMARY KEY (session_id, exercise_id),
    CONSTRAINT fk_session_exercises_session
        FOREIGN KEY (session_id) REFERENCES therapy_sessions(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_session_exercises_exercise
        FOREIGN KEY (exercise_id) REFERENCES exercises(id)
        ON DELETE RESTRICT
);

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
    CONSTRAINT fk_files_patient
        FOREIGN KEY (patient_persona_id) REFERENCES personas(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_files_session
        FOREIGN KEY (session_id) REFERENCES therapy_sessions(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_files_uploaded_by
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL,
    provider_payment_id VARCHAR(120) NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'MXN',
    status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    reference VARCHAR(120) NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level ENUM('info','warning','error','audit') NOT NULL DEFAULT 'info',
    actor_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT UNSIGNED NULL,
    message VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    CONSTRAINT fk_logs_actor_user
        FOREIGN KEY (actor_user_id) REFERENCES users(id)
        ON DELETE SET NULL
);
