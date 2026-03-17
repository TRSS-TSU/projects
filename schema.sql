CREATE TABLE IF NOT EXISTS squadrons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS pocs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    phone TEXT NOT NULL DEFAULT '',
    flight TEXT NOT NULL DEFAULT '',
    squadron_id INTEGER REFERENCES squadrons(id)
);

CREATE TABLE IF NOT EXISTS project_pocs (
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    poc_id INTEGER NOT NULL REFERENCES pocs(id),
    PRIMARY KEY (project_id, poc_id)
);

CREATE TABLE IF NOT EXISTS statuses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    sort_order INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS software_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS deployment_targets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    course_number TEXT NOT NULL DEFAULT '',
    squadron_id INTEGER REFERENCES squadrons(id)
);

CREATE TABLE IF NOT EXISTS project_courses (
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    course_id INTEGER NOT NULL REFERENCES courses(id),
    PRIMARY KEY (project_id, course_id)
);

CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_number TEXT NOT NULL UNIQUE,
    project_name TEXT NOT NULL DEFAULT '',
    squadron_id INTEGER REFERENCES squadrons(id),
    poc_id INTEGER REFERENCES pocs(id),
    description TEXT NOT NULL,
    status_id INTEGER NOT NULL REFERENCES statuses(id),
    start_date DATE NOT NULL,
    completion_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS project_software_tags (
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES software_tags(id),
    PRIMARY KEY (project_id, tag_id)
);

CREATE TABLE IF NOT EXISTS project_deployment_targets (
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    target_id INTEGER NOT NULL REFERENCES deployment_targets(id),
    PRIMARY KEY (project_id, target_id)
);

CREATE TABLE IF NOT EXISTS project_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    poc_name TEXT NOT NULL,
    poc_squadron TEXT NOT NULL DEFAULT '',
    course_number TEXT NOT NULL DEFAULT '',
    course_name TEXT NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    potential_impact TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'pending',
    status_id INTEGER REFERENCES statuses(id),
    squadron_id INTEGER REFERENCES squadrons(id),
    course_id INTEGER REFERENCES courses(id),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
